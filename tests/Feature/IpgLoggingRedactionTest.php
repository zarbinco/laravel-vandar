<?php

declare(strict_types=1);

namespace Zarbinco\LaravelVandar\Tests\Feature;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Zarbinco\LaravelVandar\Facades\Vandar;
use Zarbinco\LaravelVandar\Tests\TestCase;

final class IpgLoggingRedactionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('vandar.ipg.api_key', 'fake-ipg-api-key');
        config()->set('vandar.logging.enabled', true);
    }

    public function test_ipg_logging_redacts_sensitive_payload_and_response_values(): void
    {
        Log::spy();

        Http::fake([
            'https://ipg.vandar.io/*' => Http::response([
                'api_key' => 'fake-ipg-api-key',
                'token' => 'fake-payment-token',
                'cardNumber' => 'fake-card',
                'CID' => 'fake-cid',
                'mobile' => 'fake-mobile',
                'national_code' => 'fake-national-code',
            ], 200),
        ]);

        Vandar::ipg()->send([
            'amount' => 100000,
            'callback_url' => 'fake-callback-url',
            'factorNumber' => 'fake-factor-number',
            'token' => 'fake-payment-token',
            'cardNumber' => 'fake-card',
            'CID' => 'fake-cid',
            'mobile' => 'fake-mobile',
            'national_code' => 'fake-national-code',
        ]);

        Log::shouldHaveReceived('debug')
            ->once()
            ->withArgs(function (string $message, array $context): bool {
                $encoded = json_encode($context);

                return $message === 'Vandar HTTP request'
                    && is_string($encoded)
                    && ! str_contains($encoded, 'fake-ipg-api-key')
                    && ! str_contains($encoded, 'fake-payment-token')
                    && ! str_contains($encoded, 'fake-card')
                    && ! str_contains($encoded, 'fake-cid')
                    && ! str_contains($encoded, 'fake-mobile')
                    && ! str_contains($encoded, 'fake-national-code')
                    && str_contains($encoded, '[redacted]');
            });
    }
}
