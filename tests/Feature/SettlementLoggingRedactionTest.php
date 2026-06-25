<?php

declare(strict_types=1);

namespace Zarbinco\LaravelVandar\Tests\Feature;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Zarbinco\LaravelVandar\Facades\Vandar;
use Zarbinco\LaravelVandar\Tests\TestCase;

final class SettlementLoggingRedactionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('vandar.business', 'test-business');
        config()->set('vandar.tokens.access_token', 'fake-access-token');
        config()->set('vandar.tokens.refresh_token', 'fake-refresh-token');
        config()->set('vandar.logging.enabled', true);
    }

    public function test_settlement_logging_redacts_sensitive_payload_and_response_values(): void
    {
        Log::spy();

        Http::fake([
            'https://api.vandar.io/*' => Http::response([
                'iban' => 'fake-iban',
                'amount' => 100000,
                'track_id' => 'fake-track-id',
                'settlement_id' => 'fake-settlement-id',
                'refnumber' => 'fake-refnumber',
            ], 200),
        ]);

        Vandar::settlements()->create([
            'iban' => 'fake-iban',
            'amount' => 100000,
            'track_id' => 'fake-track-id',
        ]);

        Log::shouldHaveReceived('debug')
            ->once()
            ->withArgs(function (string $message, array $context): bool {
                $encoded = json_encode($context);

                return $message === 'Vandar HTTP request'
                    && is_string($encoded)
                    && ! str_contains($encoded, 'fake-iban')
                    && ! str_contains($encoded, '100000')
                    && ! str_contains($encoded, 'fake-track-id')
                    && ! str_contains($encoded, 'fake-settlement-id')
                    && ! str_contains($encoded, 'fake-refnumber')
                    && str_contains($encoded, '[redacted]');
            });
    }
}
