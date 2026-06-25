<?php

declare(strict_types=1);

namespace Zarbinco\LaravelVandar\Tests\Feature;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Zarbinco\LaravelVandar\Http\VandarClient;
use Zarbinco\LaravelVandar\Tests\TestCase;

final class HttpClientLoggingRedactionTest extends TestCase
{
    public function test_logging_sanitizes_urls_and_redacts_payloads_and_responses(): void
    {
        config()->set('vandar.logging.enabled', true);
        Log::spy();

        Http::fake([
            'https://api.vandar.io/*' => Http::response([
                'access_token' => 'fake-access-token',
                'normal' => 'yes',
            ], 200, [
                'Authorization' => 'Bearer fake-authorization-token',
            ]),
        ]);

        $this->app->make(VandarClient::class)->get(
            'api',
            '/v1/test?token=fake-query-token&normal=yes',
            ['refreshtoken' => 'fake-refresh-token', 'normal' => 'yes'],
            auth: false,
        );

        Log::shouldHaveReceived('debug')
            ->once()
            ->withArgs(function (string $message, array $context): bool {
                $encoded = json_encode($context);

                return $message === 'Vandar HTTP request'
                    && is_string($encoded)
                    && ! str_contains($encoded, 'fake-query-token')
                    && ! str_contains($encoded, 'fake-refresh-token')
                    && ! str_contains($encoded, 'fake-access-token')
                    && ! str_contains($encoded, 'fake-authorization-token')
                    && str_contains($encoded, '%5Bredacted%5D')
                    && str_contains($encoded, '[redacted]')
                    && str_contains($encoded, 'normal=yes');
            });
    }
}
