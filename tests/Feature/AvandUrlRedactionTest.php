<?php

declare(strict_types=1);

namespace Zarbinco\LaravelVandar\Tests\Feature;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Zarbinco\LaravelVandar\Facades\Vandar;
use Zarbinco\LaravelVandar\Tests\TestCase;

final class AvandUrlRedactionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('vandar.business', 'test-business');
        config()->set('vandar.tokens.access_token', 'fake-access-token');
        config()->set('vandar.tokens.refresh_token', 'fake-refresh-token');
        config()->set('vandar.logging.enabled', true);
    }

    public function test_last_balance_log_redacts_iban_path_segment(): void
    {
        Log::spy();

        Http::fake(['https://api.vandar.io/*' => Http::response(['ok' => true], 200)]);

        Vandar::avand()->lastBalance('IR123456789');

        Http::assertSent(fn (Request $request): bool => $request->url() === 'https://api.vandar.io/v3/business/test-business/settlement/account/IR123456789/last-balance');

        Log::shouldHaveReceived('debug')
            ->once()
            ->withArgs(function (string $message, array $context): bool {
                $encoded = json_encode($context);

                return $message === 'Vandar HTTP request'
                    && is_string($encoded)
                    && ! str_contains($encoded, 'IR123456789')
                    && ($context['url'] ?? null) === 'https://api.vandar.io/v3/business/test-business/settlement/account/[redacted]/last-balance';
            });
    }

    public function test_transaction_label_log_redacts_iban_tracking_code_and_label(): void
    {
        Log::spy();

        Http::fake(['https://api.vandar.io/*' => Http::response(['label' => 'fake-label'], 200)]);

        Vandar::avand()->addTransactionLabel('IR123456789', 'fake-tracking-code', ['label' => 'fake-label']);

        Http::assertSent(fn (Request $request): bool => $request->url() === 'https://api.vandar.io/v3/business/test-business/settlement/account/IR123456789/transaction/fake-tracking-code/label');

        Log::shouldHaveReceived('debug')
            ->once()
            ->withArgs(function (string $message, array $context): bool {
                $encoded = json_encode($context);

                return $message === 'Vandar HTTP request'
                    && is_string($encoded)
                    && ! str_contains($encoded, 'IR123456789')
                    && ! str_contains($encoded, 'fake-tracking-code')
                    && ! str_contains($encoded, 'fake-label')
                    && ($context['url'] ?? null) === 'https://api.vandar.io/v3/business/test-business/settlement/account/[redacted]/transaction/[redacted]/label';
            });
    }

    public function test_suspicious_payment_log_redacts_id_path_segment(): void
    {
        Log::spy();

        Http::fake(['https://api.vandar.io/*' => Http::response(['ok' => true], 200)]);

        Vandar::avand()->suspiciousPayment('fake-suspicious-payment-id', [
            'payment_identifier' => 'fake-payment-identifier',
        ]);

        Http::assertSent(fn (Request $request): bool => $request->url() === 'https://api.vandar.io/v3/business/test-business/cash-in/suspicious-payment/fake-suspicious-payment-id');

        Log::shouldHaveReceived('debug')
            ->once()
            ->withArgs(function (string $message, array $context): bool {
                $encoded = json_encode($context);

                return $message === 'Vandar HTTP request'
                    && is_string($encoded)
                    && ! str_contains($encoded, 'fake-suspicious-payment-id')
                    && ! str_contains($encoded, 'fake-payment-identifier')
                    && ($context['url'] ?? null) === 'https://api.vandar.io/v3/business/test-business/cash-in/suspicious-payment/[redacted]';
            });
    }
}
