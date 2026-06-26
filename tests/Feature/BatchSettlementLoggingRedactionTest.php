<?php

declare(strict_types=1);

namespace Zarbinco\LaravelVandar\Tests\Feature;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Zarbinco\LaravelVandar\Facades\Vandar;
use Zarbinco\LaravelVandar\Tests\TestCase;

final class BatchSettlementLoggingRedactionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('vandar.business', 'test-business');
        config()->set('vandar.tokens.access_token', 'fake-access-token');
        config()->set('vandar.tokens.refresh_token', 'fake-refresh-token');
        config()->set('vandar.logging.enabled', true);
    }

    public function test_batch_settlement_logging_redacts_sensitive_values(): void
    {
        $logger = Log::spy();

        Http::fake([
            'https://batch.vandar.io/*' => Http::response([
                'batch_id' => 'fake-batch-id',
                'amount' => 100000,
                'iban' => 'fake-iban',
                'track_id' => 'fake-track-id',
            ], 200),
        ]);

        Vandar::batchSettlements()->create([
            'settlements' => [
                [
                    'iban' => 'fake-iban',
                    'amount' => 100000,
                    'track_id' => 'fake-track-id',
                ],
            ],
        ]);

        $logger->shouldHaveReceived('debug')
            ->once()
            ->withArgs(function (string $message, array $context): bool {
                $encoded = json_encode($context);

                return $message === 'Vandar HTTP request'
                    && is_string($encoded)
                    && ! str_contains($encoded, 'fake-batch-id')
                    && ! str_contains($encoded, '100000')
                    && ! str_contains($encoded, 'fake-iban')
                    && ! str_contains($encoded, 'fake-track-id')
                    && str_contains($encoded, '[redacted]');
            });
    }
}
