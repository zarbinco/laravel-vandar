<?php

declare(strict_types=1);

namespace Zarbinco\LaravelVandar\Tests\Feature;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Zarbinco\LaravelVandar\Facades\Vandar;
use Zarbinco\LaravelVandar\Tests\TestCase;

final class AvandCashInIdentifierTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('vandar.business', 'test-business');
        config()->set('vandar.tokens.access_token', 'fake-access-token');
        config()->set('vandar.tokens.refresh_token', 'fake-refresh-token');
    }

    public function test_code_and_alias_call_cash_in_code_endpoint(): void
    {
        Http::fake(['https://api.vandar.io/*' => Http::response(['code' => 'fake-code'], 200)]);

        Vandar::avand()->code();
        Vandar::avand()->cashInCode();

        Http::assertSent(fn (Request $request): bool => $request->method() === 'GET'
            && $request->url() === 'https://api.vandar.io/v3/business/test-business/cash-in/code');
        Http::assertSentCount(2);
    }

    public function test_pic_transactions_calls_endpoint_with_query(): void
    {
        Http::fake(['https://api.vandar.io/*' => Http::response(['data' => []], 200)]);

        Vandar::avand()->picTransactions(['page' => 1]);

        Http::assertSent(fn (Request $request): bool => $request->method() === 'GET'
            && str_starts_with($request->url(), 'https://api.vandar.io/v3/business/test-business/cash-in/pic/transactions')
            && str_contains($request->url(), 'page=1'));
    }

    public function test_suspicious_payments_calls_endpoint_with_query(): void
    {
        Http::fake(['https://api.vandar.io/*' => Http::response(['data' => []], 200)]);

        Vandar::avand()->suspiciousPayments(['page' => 1]);

        Http::assertSent(fn (Request $request): bool => $request->method() === 'GET'
            && str_starts_with($request->url(), 'https://api.vandar.io/v3/business/test-business/cash-in/suspicious-payment')
            && str_contains($request->url(), 'page=1'));
    }

    public function test_suspicious_payment_and_alias_call_resolution_endpoint(): void
    {
        Http::fake(['https://api.vandar.io/*' => Http::response(['ok' => true], 200)]);

        Vandar::avand()->suspiciousPayment('fake-suspicious-payment-id', ['status' => 'resolved']);
        Vandar::avand()->resolveSuspiciousPayment('fake-second-suspicious-payment-id');

        Http::assertSent(fn (Request $request): bool => $request->method() === 'POST'
            && $request->url() === 'https://api.vandar.io/v3/business/test-business/cash-in/suspicious-payment/fake-suspicious-payment-id'
            && $request['status'] === 'resolved');
        Http::assertSent(fn (Request $request): bool => $request->method() === 'POST'
            && $request->url() === 'https://api.vandar.io/v3/business/test-business/cash-in/suspicious-payment/fake-second-suspicious-payment-id');
    }

    public function test_suspicious_payment_writes_are_not_retried_automatically(): void
    {
        config()->set('vandar.http.retry.enabled', true);
        config()->set('vandar.http.retry.times', 3);

        Http::fake(['https://api.vandar.io/*' => Http::response(['message' => 'Failed'], 500)]);

        Vandar::avand()->suspiciousPayment('fake-suspicious-payment-id');
        Vandar::avand()->resolveSuspiciousPayment('fake-second-suspicious-payment-id');

        Http::assertSentCount(2);
    }
}
