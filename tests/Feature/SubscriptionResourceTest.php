<?php

declare(strict_types=1);

namespace Zarbinco\LaravelVandar\Tests\Feature;

use Closure;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use PHPUnit\Framework\Attributes\DataProvider;
use Zarbinco\LaravelVandar\DTO\VandarResponse;
use Zarbinco\LaravelVandar\Facades\Vandar;
use Zarbinco\LaravelVandar\Tests\TestCase;

final class SubscriptionResourceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('vandar.business', 'test-business');
        config()->set('vandar.tokens.access_token', 'fake-access-token');
        config()->set('vandar.tokens.refresh_token', 'fake-refresh-token');
        config()->set('vandar.base_urls.subscription', 'https://subscription.vandar.io');

        Http::preventStrayRequests();
    }

    /**
     * @param  Closure(): VandarResponse  $call
     * @param  Closure(Request): bool|null  $assertion
     */
    #[DataProvider('mainEndpointCalls')]
    public function test_main_methods_call_official_subscription_endpoints(
        Closure $call,
        string $method,
        string $url,
        ?Closure $assertion = null,
    ): void {
        Http::fake(['https://api.vandar.io/*' => Http::response(['ok' => true], 200)]);

        $response = $call();

        $this->assertInstanceOf(VandarResponse::class, $response);
        Http::assertSentCount(1);
        Http::assertSent(fn (Request $request): bool => $request->method() === $method
            && $request->url() === $url
            && $request->hasHeader('Authorization', 'Bearer fake-access-token')
            && ($assertion === null || $assertion($request)));
    }

    public function test_banks_alias_calls_active_banks_endpoint(): void
    {
        Http::fake(['https://api.vandar.io/*' => Http::response(['data' => []], 200)]);

        Vandar::subscriptions()->banks();

        Http::assertSent(fn (Request $request): bool => $request->method() === 'GET'
            && $request->url() === 'https://api.vandar.io/v3/business/test-business/subscription/banks/actives');
    }

    public function test_authorization_url_helpers_return_redirect_urls_without_http(): void
    {
        Http::fake();

        $this->assertSame(
            'https://subscription.vandar.io/authorizations/fake-token',
            Vandar::subscriptions()->authorizationUrl('fake-token'),
        );
        $this->assertSame(
            'https://subscription.vandar.io/authorizations/fake-token',
            Vandar::subscriptions()->authorizationRedirectUrl('fake-token'),
        );
        $this->assertSame(
            'https://subscription.vandar.io/authorizations/fake-token',
            Vandar::subscriptions()->mandateUrl('fake-token'),
        );
        $this->assertSame(
            'https://subscription.vandar.io/authorizations/token%2Fwith%20space',
            Vandar::subscriptions()->authorizationUrl('token/with space'),
        );

        Http::assertNothingSent();
    }

    public function test_authorization_aliases_call_expected_endpoints(): void
    {
        Http::fake(['https://api.vandar.io/*' => Http::response(['ok' => true], 200)]);

        Vandar::subscriptions()->storeAuthorization(['track_id' => 'fake-track-id']);
        Vandar::subscriptions()->authorizations();
        Vandar::subscriptions()->cancelAuthorization('fake-authorization-id');
        Vandar::subscriptions()->destroyAuthorization('fake-authorization-id');

        Http::assertSentCount(4);
        Http::assertSent(fn (Request $request): bool => $request->method() === 'POST'
            && $request->url() === 'https://api.vandar.io/v3/business/test-business/subscription/authorization/store');
        Http::assertSent(fn (Request $request): bool => $request->method() === 'GET'
            && $request->url() === 'https://api.vandar.io/v3/business/test-business/subscription/authorization');
        Http::assertSent(fn (Request $request): bool => $request->method() === 'DELETE'
            && $request->url() === 'https://api.vandar.io/v3/business/test-business/subscription/authorization/fake-authorization-id');
    }

    public function test_withdrawal_aliases_call_expected_endpoints(): void
    {
        Http::fake(['https://api.vandar.io/*' => Http::response(['ok' => true], 200)]);

        Vandar::subscriptions()->storeWithdrawal(['amount' => 100000]);
        Vandar::subscriptions()->showWithdrawal('fake-withdrawal-id');
        Vandar::subscriptions()->withdrawal('fake-withdrawal-id');
        Vandar::subscriptions()->withdrawals();

        Http::assertSentCount(4);
        Http::assertSent(fn (Request $request): bool => $request->method() === 'POST'
            && $request->url() === 'https://api.vandar.io/v3/business/test-business/subscription/withdrawal/store');
        Http::assertSent(fn (Request $request): bool => $request->method() === 'GET'
            && $request->url() === 'https://api.vandar.io/v3/business/test-business/subscription/withdrawal/fake-withdrawal-id');
        Http::assertSent(fn (Request $request): bool => $request->method() === 'GET'
            && $request->url() === 'https://api.vandar.io/v3/business/test-business/subscription/withdrawal');
    }

    public function test_refund_aliases_call_expected_endpoints(): void
    {
        Http::fake(['https://api.vandar.io/*' => Http::response(['ok' => true], 200)]);

        Vandar::subscriptions()->storeRefund(['withdrawal_id' => 'fake-withdrawal-id']);
        Vandar::subscriptions()->showRefund('fake-refund-id');
        Vandar::subscriptions()->refund('fake-refund-id');
        Vandar::subscriptions()->refunds();

        Http::assertSentCount(4);
        Http::assertSent(fn (Request $request): bool => $request->method() === 'POST'
            && $request->url() === 'https://api.vandar.io/v3/business/test-business/subscription/refunds');
        Http::assertSent(fn (Request $request): bool => $request->method() === 'GET'
            && $request->url() === 'https://api.vandar.io/v3/business/test-business/subscription/refunds/fake-refund-id');
        Http::assertSent(fn (Request $request): bool => $request->method() === 'GET'
            && $request->url() === 'https://api.vandar.io/v3/business/test-business/subscription/refunds');
    }

    public function test_business_and_dynamic_path_segments_are_encoded(): void
    {
        Http::fake(['https://api.vandar.io/*' => Http::response(['ok' => true], 200)]);

        Vandar::subscriptions()->verifyAuthorization(
            'authorization/id with space',
            ['track_id' => 'fake-track-id'],
            'business/id with space',
        );

        Http::assertSent(fn (Request $request): bool => $request->url() === 'https://api.vandar.io/v3/business/business%2Fid%20with%20space/subscription/authorization/authorization%2Fid%20with%20space/verify');
    }

    #[DataProvider('sideEffectCalls')]
    public function test_side_effect_methods_are_not_retried_by_default(Closure $call): void
    {
        config()->set('vandar.rate_limit.max_retry_after_seconds', 0);

        Http::fake([
            'https://api.vandar.io/*' => Http::sequence()
                ->push(['message' => 'Too many requests'], 429, ['Retry-After' => '0'])
                ->push(['ok' => true], 200),
        ]);

        $response = $call();

        $this->assertTrue($response->tooManyRequests());
        Http::assertSentCount(1);
    }

    public function test_logging_redacts_subscription_identifiers_and_authorization_query(): void
    {
        config()->set('vandar.logging.enabled', true);
        Log::spy();

        Http::fake(['https://api.vandar.io/*' => Http::response(['ok' => true], 200)]);

        Vandar::subscriptions()->withdrawalsForAuthorization('fake-authorization-id', [
            'page' => 1,
            'q' => 'user-supplied-value',
        ]);

        Log::shouldHaveReceived('debug')
            ->once()
            ->withArgs(function (string $message, array $context): bool {
                $encoded = json_encode($context);

                return $message === 'Vandar HTTP request'
                    && is_string($encoded)
                    && ($context['url'] ?? null) === 'https://api.vandar.io/v3/business/test-business/subscription/withdrawal'
                    && ($context['payload']['q'] ?? null) === '[redacted]'
                    && ($context['payload']['page'] ?? null) === 1
                    && ! str_contains($encoded, 'fake-authorization-id')
                    && ! str_contains($encoded, 'user-supplied-value')
                    && str_contains($encoded, '[redacted]');
            });
    }

    /**
     * @return array<string, array{Closure(): VandarResponse, string, string, 3?: Closure(Request): bool}>
     */
    public static function mainEndpointCalls(): array
    {
        return [
            'active banks' => [
                static fn (): VandarResponse => Vandar::subscriptions()->activeBanks(),
                'GET',
                'https://api.vandar.io/v3/business/test-business/subscription/banks/actives',
            ],
            'create authorization' => [
                static fn (): VandarResponse => Vandar::subscriptions()->createAuthorization(['track_id' => 'fake-track-id']),
                'POST',
                'https://api.vandar.io/v3/business/test-business/subscription/authorization/store',
                static fn (Request $request): bool => $request['track_id'] === 'fake-track-id',
            ],
            'verify authorization' => [
                static fn (): VandarResponse => Vandar::subscriptions()->verifyAuthorization('fake-authorization-id', ['status' => 'ok']),
                'PATCH',
                'https://api.vandar.io/v3/business/test-business/subscription/authorization/fake-authorization-id/verify',
                static fn (Request $request): bool => $request['status'] === 'ok',
            ],
            'search authorization' => [
                static fn (): VandarResponse => Vandar::subscriptions()->searchAuthorization('fake-authorization-id', ['page' => 1]),
                'GET',
                'https://api.vandar.io/v3/business/test-business/subscription/authorization/fake-authorization-id/search?page=1',
            ],
            'list authorizations' => [
                static fn (): VandarResponse => Vandar::subscriptions()->listAuthorizations(['page' => 1]),
                'GET',
                'https://api.vandar.io/v3/business/test-business/subscription/authorization?page=1',
            ],
            'authorization calculation' => [
                static fn (): VandarResponse => Vandar::subscriptions()->authorizationCalculation('fake-authorization-id', ['amount' => 100000]),
                'GET',
                'https://api.vandar.io/v3/business/test-business/subscription/authorization/fake-authorization-id/calculation?amount=100000',
            ],
            'delete authorization' => [
                static fn (): VandarResponse => Vandar::subscriptions()->deleteAuthorization('fake-authorization-id'),
                'DELETE',
                'https://api.vandar.io/v3/business/test-business/subscription/authorization/fake-authorization-id',
            ],
            'create withdrawal' => [
                static fn (): VandarResponse => Vandar::subscriptions()->createWithdrawal(['amount' => 100000]),
                'POST',
                'https://api.vandar.io/v3/business/test-business/subscription/withdrawal/store',
                static fn (Request $request): bool => $request['amount'] === 100000,
            ],
            'find withdrawal' => [
                static fn (): VandarResponse => Vandar::subscriptions()->findWithdrawal('fake-withdrawal-id'),
                'GET',
                'https://api.vandar.io/v3/business/test-business/subscription/withdrawal/fake-withdrawal-id',
            ],
            'withdrawal by track id' => [
                static fn (): VandarResponse => Vandar::subscriptions()->withdrawalByTrackId('fake-track-id'),
                'GET',
                'https://api.vandar.io/v3/business/test-business/subscription/withdrawal/track-id/fake-track-id',
            ],
            'list withdrawals' => [
                static fn (): VandarResponse => Vandar::subscriptions()->listWithdrawals(['page' => 1]),
                'GET',
                'https://api.vandar.io/v3/business/test-business/subscription/withdrawal?page=1',
            ],
            'withdrawals for authorization' => [
                static fn (): VandarResponse => Vandar::subscriptions()->withdrawalsForAuthorization('fake-authorization-id', ['page' => 1]),
                'GET',
                'https://api.vandar.io/v3/business/test-business/subscription/withdrawal?page=1&q=fake-authorization-id',
            ],
            'update withdrawal' => [
                static fn (): VandarResponse => Vandar::subscriptions()->updateWithdrawal('fake-withdrawal-id', ['status' => 'canceled']),
                'PUT',
                'https://api.vandar.io/v3/business/test-business/subscription/withdrawal/fake-withdrawal-id',
                static fn (Request $request): bool => $request['status'] === 'canceled',
            ],
            'create refund' => [
                static fn (): VandarResponse => Vandar::subscriptions()->createRefund(['withdrawal_id' => 'fake-withdrawal-id']),
                'POST',
                'https://api.vandar.io/v3/business/test-business/subscription/refunds',
                static fn (Request $request): bool => $request['withdrawal_id'] === 'fake-withdrawal-id',
            ],
            'find refund' => [
                static fn (): VandarResponse => Vandar::subscriptions()->findRefund('fake-refund-id'),
                'GET',
                'https://api.vandar.io/v3/business/test-business/subscription/refunds/fake-refund-id',
            ],
            'list refunds' => [
                static fn (): VandarResponse => Vandar::subscriptions()->listRefunds(['page' => 1]),
                'GET',
                'https://api.vandar.io/v3/business/test-business/subscription/refunds?page=1',
            ],
        ];
    }

    /**
     * @return array<string, array{Closure(): VandarResponse}>
     */
    public static function sideEffectCalls(): array
    {
        return [
            'create authorization' => [static fn (): VandarResponse => Vandar::subscriptions()->createAuthorization(['track_id' => 'fake-track-id'])],
            'verify authorization' => [static fn (): VandarResponse => Vandar::subscriptions()->verifyAuthorization('fake-authorization-id')],
            'delete authorization' => [static fn (): VandarResponse => Vandar::subscriptions()->deleteAuthorization('fake-authorization-id')],
            'create withdrawal' => [static fn (): VandarResponse => Vandar::subscriptions()->createWithdrawal(['amount' => 100000])],
            'update withdrawal' => [static fn (): VandarResponse => Vandar::subscriptions()->updateWithdrawal('fake-withdrawal-id', ['status' => 'canceled'])],
            'create refund' => [static fn (): VandarResponse => Vandar::subscriptions()->createRefund(['withdrawal_id' => 'fake-withdrawal-id'])],
        ];
    }
}
