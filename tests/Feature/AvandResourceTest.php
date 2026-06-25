<?php

declare(strict_types=1);

namespace Zarbinco\LaravelVandar\Tests\Feature;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Zarbinco\LaravelVandar\DTO\VandarResponse;
use Zarbinco\LaravelVandar\Exceptions\VandarBusinessNotConfiguredException;
use Zarbinco\LaravelVandar\Facades\Vandar;
use Zarbinco\LaravelVandar\Tests\TestCase;

final class AvandResourceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('vandar.business', 'test-business');
        config()->set('vandar.tokens.access_token', 'fake-access-token');
        config()->set('vandar.tokens.refresh_token', 'fake-refresh-token');
    }

    public function test_account_calls_cash_in_account_endpoint(): void
    {
        Http::fake(['https://api.vandar.io/*' => Http::response(['data' => []], 200)]);

        $response = Vandar::avand()->account();

        $this->assertInstanceOf(VandarResponse::class, $response);
        Http::assertSent(fn (Request $request): bool => $request->method() === 'GET'
            && $request->url() === 'https://api.vandar.io/v3/business/test-business/cash-in/account');
    }

    public function test_cash_in_account_alias_calls_account_endpoint(): void
    {
        Http::fake(['https://api.vandar.io/*' => Http::response(['data' => []], 200)]);

        Vandar::avand()->cashInAccount();

        Http::assertSent(fn (Request $request): bool => $request->method() === 'GET'
            && $request->url() === 'https://api.vandar.io/v3/business/test-business/cash-in/account');
    }

    public function test_deposit_calls_cash_in_deposit_endpoint(): void
    {
        Http::fake(['https://api.vandar.io/*' => Http::response(['ok' => true], 200)]);

        Vandar::avand()->deposit([
            'amount' => 100000,
            'track_id' => 'fake-track-id',
        ]);

        Http::assertSent(fn (Request $request): bool => $request->method() === 'POST'
            && $request->url() === 'https://api.vandar.io/v3/business/test-business/cash-in/account/deposit'
            && $request['amount'] === 100000
            && $request['track_id'] === 'fake-track-id');
    }

    public function test_balance_calls_cash_in_balance_endpoint(): void
    {
        Http::fake(['https://api.vandar.io/*' => Http::response(['balance' => 100000], 200)]);

        Vandar::avand()->balance(['track_id' => 'fake-track-id']);

        Http::assertSent(fn (Request $request): bool => $request->method() === 'POST'
            && $request->url() === 'https://api.vandar.io/v3/business/test-business/cash-in/account/balance'
            && $request['track_id'] === 'fake-track-id');
    }

    public function test_explicit_business_argument_overrides_config(): void
    {
        Http::fake(['https://api.vandar.io/*' => Http::response(['data' => []], 200)]);

        Vandar::avand()->account('explicit-business');

        Http::assertSent(fn (Request $request): bool => $request->url() === 'https://api.vandar.io/v3/business/explicit-business/cash-in/account');
    }

    public function test_missing_business_throws(): void
    {
        config()->set('vandar.business', null);

        $this->expectException(VandarBusinessNotConfiguredException::class);

        Vandar::avand()->account();
    }

    public function test_authorization_header_is_attached_when_access_token_exists(): void
    {
        Http::fake(['https://api.vandar.io/*' => Http::response(['ok' => true], 200)]);

        Vandar::avand()->deposit(['track_id' => 'fake-track-id']);

        Http::assertSent(fn (Request $request): bool => $request->hasHeader('Authorization', 'Bearer fake-access-token'));
    }

    public function test_failed_response_does_not_throw_automatically(): void
    {
        Http::fake(['https://api.vandar.io/*' => Http::response(['message' => 'Failed'], 500)]);

        $response = Vandar::avand()->deposit(['track_id' => 'fake-track-id']);

        $this->assertInstanceOf(VandarResponse::class, $response);
        $this->assertSame(500, $response->status());
    }

    public function test_deposit_is_not_retried_automatically(): void
    {
        config()->set('vandar.http.retry.enabled', true);
        config()->set('vandar.http.retry.times', 3);

        Http::fake(['https://api.vandar.io/*' => Http::response(['message' => 'Failed'], 500)]);

        Vandar::avand()->deposit(['track_id' => 'fake-track-id']);

        Http::assertSentCount(1);
    }
}
