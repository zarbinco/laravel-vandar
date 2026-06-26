<?php

declare(strict_types=1);

namespace Zarbinco\LaravelVandar\Tests\Feature;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Zarbinco\LaravelVandar\Facades\Vandar;
use Zarbinco\LaravelVandar\Tests\TestCase;

final class RefundLoggingRedactionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('vandar.business', 'test-business');
        config()->set('vandar.tokens.access_token', 'fake-access-token');
        config()->set('vandar.tokens.refresh_token', 'fake-refresh-token');
        config()->set('vandar.logging.enabled', true);
    }

    public function test_refund_logging_redacts_sensitive_response_and_path_values(): void
    {
        $logger = Log::spy();

        Http::fake([
            'https://api.vandar.io/*' => Http::response([
                'transaction_id' => 'fake-transaction-id',
                'transId' => 'fake-trans-id',
                'cardNumber' => 'fake-card',
                'refnumber' => 'fake-refnumber',
            ], 200),
        ]);

        Vandar::refunds()->create('fake-transaction-id', [
            'amount' => 100000,
            'track_id' => 'fake-track-id',
        ]);

        $logger->shouldHaveReceived('debug')
            ->once()
            ->withArgs(function (string $message, array $context): bool {
                $encoded = json_encode($context);

                return $message === 'Vandar HTTP request'
                    && is_string($encoded)
                    && ! str_contains($encoded, 'fake-transaction-id')
                    && ! str_contains($encoded, 'fake-trans-id')
                    && ! str_contains($encoded, 'fake-card')
                    && ! str_contains($encoded, 'fake-refnumber')
                    && str_contains($encoded, '[redacted]');
            });
    }
}
