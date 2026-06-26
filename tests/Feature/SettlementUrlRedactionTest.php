<?php

declare(strict_types=1);

namespace Zarbinco\LaravelVandar\Tests\Feature;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Zarbinco\LaravelVandar\Facades\Vandar;
use Zarbinco\LaravelVandar\Tests\TestCase;

final class SettlementUrlRedactionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('vandar.business', 'test-business');
        config()->set('vandar.tokens.access_token', 'fake-access-token');
        config()->set('vandar.tokens.refresh_token', 'fake-refresh-token');
        config()->set('vandar.logging.enabled', true);
    }

    public function test_find_log_redacts_track_id_path_segment(): void
    {
        $logger = Log::spy();

        Http::fake(['https://api.vandar.io/*' => Http::response(['ok' => true], 200)]);

        Vandar::settlements()->find('fake-track-id');

        Http::assertSent(fn (Request $request): bool => $request->url() === 'https://api.vandar.io/v4/business/test-business/settlement/fake-track-id');

        $logger->shouldHaveReceived('debug')
            ->once()
            ->withArgs(function (string $message, array $context): bool {
                $encoded = json_encode($context);

                return $message === 'Vandar HTTP request'
                    && is_string($encoded)
                    && ! str_contains($encoded, 'fake-track-id')
                    && ($context['url'] ?? null) === 'https://api.vandar.io/v4/business/test-business/settlement/[redacted]';
            });
    }

    public function test_cancel_log_redacts_track_id_path_segment_and_is_not_retried(): void
    {
        config()->set('vandar.http.retry.enabled', true);
        config()->set('vandar.http.retry.times', 3);
        $logger = Log::spy();

        Http::fake(['https://api.vandar.io/*' => Http::response(['ok' => true], 200)]);

        Vandar::settlements()->cancel('fake-track-id');

        Http::assertSentCount(1);
        $logger->shouldHaveReceived('debug')
            ->once()
            ->withArgs(function (string $message, array $context): bool {
                $encoded = json_encode($context);

                return $message === 'Vandar HTTP request'
                    && is_string($encoded)
                    && ! str_contains($encoded, 'fake-track-id')
                    && ($context['url'] ?? null) === 'https://api.vandar.io/v4/business/test-business/settlement/[redacted]';
            });
    }
}
