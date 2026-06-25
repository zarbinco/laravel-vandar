<?php

declare(strict_types=1);

namespace Zarbinco\LaravelVandar\Tests\Feature;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Zarbinco\LaravelVandar\Facades\Vandar;
use Zarbinco\LaravelVandar\Tests\TestCase;

final class AvandAccountOperationsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('vandar.business', 'test-business');
        config()->set('vandar.tokens.access_token', 'fake-access-token');
        config()->set('vandar.tokens.refresh_token', 'fake-refresh-token');
    }

    public function test_last_balance_calls_account_last_balance_endpoint(): void
    {
        Http::fake(['https://api.vandar.io/*' => Http::response(['balance' => 100000], 200)]);

        Vandar::avand()->lastBalance('IR123456789');

        Http::assertSent(fn (Request $request): bool => $request->method() === 'GET'
            && $request->url() === 'https://api.vandar.io/v3/business/test-business/settlement/account/IR123456789/last-balance');
    }

    public function test_account_last_balance_alias_calls_last_balance_endpoint(): void
    {
        Http::fake(['https://api.vandar.io/*' => Http::response(['balance' => 100000], 200)]);

        Vandar::avand()->accountLastBalance('IR123456789');

        Http::assertSent(fn (Request $request): bool => $request->method() === 'GET'
            && $request->url() === 'https://api.vandar.io/v3/business/test-business/settlement/account/IR123456789/last-balance');
    }

    public function test_statement_calls_statement_endpoint_with_query(): void
    {
        Http::fake(['https://api.vandar.io/*' => Http::response(['data' => []], 200)]);

        Vandar::avand()->statement(['page' => 1]);

        Http::assertSent(fn (Request $request): bool => $request->method() === 'GET'
            && str_starts_with($request->url(), 'https://api.vandar.io/v3/business/test-business/settlement/account/statement')
            && str_contains($request->url(), 'page=1'));
    }

    public function test_realtime_statement_and_alias_call_realtime_statement_endpoint(): void
    {
        Http::fake(['https://api.vandar.io/*' => Http::response(['data' => []], 200)]);

        Vandar::avand()->realtimeStatement(['page' => 1]);
        Vandar::avand()->realTimeStatement(['page' => 2]);

        Http::assertSent(fn (Request $request): bool => $request->method() === 'GET'
            && str_starts_with($request->url(), 'https://api.vandar.io/v3/business/test-business/settlement/account/realtime-statement')
            && str_contains($request->url(), 'page=1'));
        Http::assertSent(fn (Request $request): bool => $request->method() === 'GET'
            && str_starts_with($request->url(), 'https://api.vandar.io/v3/business/test-business/settlement/account/realtime-statement')
            && str_contains($request->url(), 'page=2'));
    }

    public function test_add_transaction_label_calls_label_endpoint(): void
    {
        Http::fake(['https://api.vandar.io/*' => Http::response(['ok' => true], 200)]);

        Vandar::avand()->addTransactionLabel('IR123456789', 'fake-tracking-code', ['label' => 'paid']);

        Http::assertSent(fn (Request $request): bool => $request->method() === 'POST'
            && $request->url() === 'https://api.vandar.io/v3/business/test-business/settlement/account/IR123456789/transaction/fake-tracking-code/label'
            && $request['label'] === 'paid');
    }

    public function test_remove_transaction_label_and_alias_call_label_endpoint(): void
    {
        Http::fake(['https://api.vandar.io/*' => Http::response(['ok' => true], 200)]);

        Vandar::avand()->removeTransactionLabel('IR123456789', 'fake-tracking-code', ['label' => 'paid']);
        Vandar::avand()->deleteTransactionLabel('IR987654321', 'fake-second-tracking-code');

        Http::assertSent(fn (Request $request): bool => $request->method() === 'DELETE'
            && $request->url() === 'https://api.vandar.io/v3/business/test-business/settlement/account/IR123456789/transaction/fake-tracking-code/label'
            && $request['label'] === 'paid');
        Http::assertSent(fn (Request $request): bool => $request->method() === 'DELETE'
            && $request->url() === 'https://api.vandar.io/v3/business/test-business/settlement/account/IR987654321/transaction/fake-second-tracking-code/label');
    }

    public function test_transaction_label_writes_are_not_retried_automatically(): void
    {
        config()->set('vandar.http.retry.enabled', true);
        config()->set('vandar.http.retry.times', 3);

        Http::fake(['https://api.vandar.io/*' => Http::response(['message' => 'Failed'], 500)]);

        Vandar::avand()->addTransactionLabel('IR123456789', 'fake-tracking-code');
        Vandar::avand()->removeTransactionLabel('IR123456789', 'fake-tracking-code');
        Vandar::avand()->deleteTransactionLabel('IR123456789', 'fake-tracking-code');

        Http::assertSentCount(3);
    }
}
