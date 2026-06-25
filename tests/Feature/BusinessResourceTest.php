<?php

declare(strict_types=1);

namespace Zarbinco\LaravelVandar\Tests\Feature;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Zarbinco\LaravelVandar\DTO\VandarResponse;
use Zarbinco\LaravelVandar\Exceptions\VandarBusinessNotConfiguredException;
use Zarbinco\LaravelVandar\Facades\Vandar;
use Zarbinco\LaravelVandar\Tests\TestCase;

final class BusinessResourceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('vandar.business', 'test-business');
        config()->set('vandar.tokens.access_token', 'fake-access-token');
        config()->set('vandar.tokens.refresh_token', 'fake-refresh-token');
    }

    public function test_info_calls_business_endpoint(): void
    {
        Http::fake(['https://api.vandar.io/*' => Http::response(['ok' => true], 200)]);

        $response = Vandar::business()->info();

        $this->assertInstanceOf(VandarResponse::class, $response);
        Http::assertSent(fn (Request $request): bool => $request->method() === 'GET'
            && $request->url() === 'https://api.vandar.io/v2/business/test-business');
    }

    public function test_users_calls_business_iam_endpoint(): void
    {
        Http::fake(['https://api.vandar.io/*' => Http::response(['ok' => true], 200)]);

        Vandar::business()->users();

        Http::assertSent(fn (Request $request): bool => $request->method() === 'GET'
            && $request->url() === 'https://api.vandar.io/v2/business/test-business/iam');
    }

    public function test_balance_calls_business_balance_endpoint(): void
    {
        Http::fake(['https://api.vandar.io/*' => Http::response(['ok' => true], 200)]);

        Vandar::business()->balance();

        Http::assertSent(fn (Request $request): bool => $request->method() === 'GET'
            && $request->url() === 'https://api.vandar.io/v2/business/test-business/balance');
    }

    public function test_transactions_calls_v3_business_transaction_endpoint_with_query(): void
    {
        Http::fake(['https://api.vandar.io/*' => Http::response(['ok' => true], 200)]);

        Vandar::business()->transactions(['page' => 1]);

        Http::assertSent(fn (Request $request): bool => $request->method() === 'GET'
            && str_starts_with($request->url(), 'https://api.vandar.io/v3/business/test-business/transaction')
            && str_contains($request->url(), 'page=1'));
    }

    public function test_explicit_business_argument_overrides_config(): void
    {
        Http::fake(['https://api.vandar.io/*' => Http::response(['ok' => true], 200)]);

        Vandar::business()->info('explicit-business');

        Http::assertSent(fn (Request $request): bool => $request->url() === 'https://api.vandar.io/v2/business/explicit-business');
    }

    public function test_missing_business_throws(): void
    {
        config()->set('vandar.business', null);

        $this->expectException(VandarBusinessNotConfiguredException::class);

        Vandar::business()->info();
    }

    public function test_authorization_header_is_attached_when_token_exists(): void
    {
        Http::fake(['https://api.vandar.io/*' => Http::response(['ok' => true], 200)]);

        Vandar::business()->info();

        Http::assertSent(fn (Request $request): bool => $request->hasHeader('Authorization', 'Bearer fake-access-token'));
    }

    public function test_failed_response_does_not_throw_automatically(): void
    {
        Http::fake(['https://api.vandar.io/*' => Http::response(['message' => 'Failed'], 422)]);

        $response = Vandar::business()->info();

        $this->assertInstanceOf(VandarResponse::class, $response);
        $this->assertSame(422, $response->status());
    }
}
