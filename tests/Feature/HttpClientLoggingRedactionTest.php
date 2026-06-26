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
        $logger = Log::spy();

        Http::fake([
            'https://api.vandar.io/*' => Http::response([
                'access_token' => 'fake-access-token',
                'factorNumber' => 'fake-factor-number',
                'settlement_id' => 'fake-settlement-id',
                'customer_id' => 'fake-customer-id',
                'data' => [
                    'transaction_id' => 'fake-transaction-id',
                    'transId' => 'fake-trans-id',
                    'authorization_id' => 'fake-authorization-id',
                    'withdrawal_id' => 'fake-withdrawal-id',
                    'card_number' => 'fake-card-number',
                    'iban' => 'fake-iban',
                    'sheba' => 'fake-sheba',
                    'mobile' => 'fake-mobile',
                    'national_code' => 'fake-national-code',
                ],
                'normal' => 'yes',
            ], 200, [
                'Authorization' => 'Bearer fake-authorization-token',
            ]),
        ]);

        $this->app->make(VandarClient::class)->get(
            'api',
            '/v1/test?token=fake-query-token&factorNumber=fake-factor-query&customerId=fake-customer-query&normal=yes',
            [
                'refreshtoken' => 'fake-refresh-token',
                'api_key' => 'fake-api-key',
                'card_number' => 'fake-payload-card-number',
                'normal' => 'yes',
            ],
            auth: false,
        );

        $logger->shouldHaveReceived('debug')
            ->once()
            ->withArgs(function (string $message, array $context): bool {
                $encoded = json_encode($context);

                return $message === 'Vandar HTTP request'
                    && is_string($encoded)
                    && ! str_contains($encoded, 'fake-query-token')
                    && ! str_contains($encoded, 'fake-factor-query')
                    && ! str_contains($encoded, 'fake-customer-query')
                    && ! str_contains($encoded, 'fake-refresh-token')
                    && ! str_contains($encoded, 'fake-api-key')
                    && ! str_contains($encoded, 'fake-payload-card-number')
                    && ! str_contains($encoded, 'fake-access-token')
                    && ! str_contains($encoded, 'fake-factor-number')
                    && ! str_contains($encoded, 'fake-settlement-id')
                    && ! str_contains($encoded, 'fake-customer-id')
                    && ! str_contains($encoded, 'fake-transaction-id')
                    && ! str_contains($encoded, 'fake-trans-id')
                    && ! str_contains($encoded, 'fake-authorization-id')
                    && ! str_contains($encoded, 'fake-withdrawal-id')
                    && ! str_contains($encoded, 'fake-card-number')
                    && ! str_contains($encoded, 'fake-iban')
                    && ! str_contains($encoded, 'fake-sheba')
                    && ! str_contains($encoded, 'fake-mobile')
                    && ! str_contains($encoded, 'fake-national-code')
                    && ! str_contains($encoded, 'fake-authorization-token')
                    && str_contains($encoded, '%5Bredacted%5D')
                    && str_contains($encoded, '[redacted]')
                    && str_contains($encoded, 'normal=yes');
            });
    }
}
