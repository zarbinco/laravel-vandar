<?php

declare(strict_types=1);

namespace Zarbinco\LaravelVandar\Tests\Feature;

use Closure;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use Zarbinco\LaravelVandar\DTO\VandarResponse;
use Zarbinco\LaravelVandar\Exceptions\VandarRequestException;
use Zarbinco\LaravelVandar\Facades\Vandar;
use Zarbinco\LaravelVandar\Http\PendingVandarRequest;
use Zarbinco\LaravelVandar\Http\VandarClient;
use Zarbinco\LaravelVandar\Tests\TestCase;

final class HttpRetryReliabilityTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('vandar.business', 'test-business');
        config()->set('vandar.ipg.api_key', 'fake-ipg-api-key');
        config()->set('vandar.tokens.access_token', 'fake-access-token');
        config()->set('vandar.tokens.refresh_token', 'fake-refresh-token');
        config()->set('vandar.rate_limit.max_retry_after_seconds', 0);
    }

    protected function tearDown(): void
    {
        PendingVandarRequest::sleepUsing(null);

        parent::tearDown();
    }

    public function test_get_request_retries_retryable_server_response_when_http_retry_enabled(): void
    {
        config()->set('vandar.http.retry.enabled', true);
        config()->set('vandar.http.retry.times', 2);
        config()->set('vandar.http.retry.sleep_ms', 0);

        Http::fake([
            'https://api.vandar.io/*' => Http::sequence()
                ->push(['message' => 'Temporary upstream error'], 500)
                ->push(['data' => ['ok' => true]], 200),
        ]);

        $response = $this->app->make(VandarClient::class)->get('api', '/v2/retryable', auth: false);

        $this->assertTrue($response->successful());
        $this->assertSame(['ok' => true], $response->data());
        Http::assertSentCount(2);
    }

    public function test_get_server_response_is_not_retried_when_http_retry_disabled(): void
    {
        config()->set('vandar.http.retry.enabled', false);

        Http::fake([
            'https://api.vandar.io/*' => Http::sequence()
                ->push(['message' => 'Temporary upstream error'], 500)
                ->push(['data' => ['ok' => true]], 200),
        ]);

        $response = $this->app->make(VandarClient::class)->get('api', '/v2/retryable', auth: false);

        $this->assertSame(500, $response->status());
        Http::assertSentCount(1);
    }

    public function test_connection_timeout_is_wrapped_in_package_exception_with_safe_context(): void
    {
        Http::fake([
            'https://api.vandar.io/*' => fn (): never => throw new ConnectionException('cURL error 28: Operation timed out'),
        ]);

        try {
            $this->app->make(VandarClient::class)->post('api', '/v2/manual', [
                'amount' => 100000,
                'track_id' => 'fake-track-id',
            ], auth: false);
        } catch (VandarRequestException $exception) {
            $context = $exception->context();

            $this->assertSame(0, $exception->status());
            $this->assertSame('POST', $context['response']['method'] ?? null);
            $this->assertSame('https://api.vandar.io/v2/manual', $context['response']['url'] ?? null);
            $this->assertSame('[redacted]', $context['response']['payload']['amount'] ?? null);
            $this->assertSame('[redacted]', $context['response']['payload']['track_id'] ?? null);

            return;
        }

        $this->fail('Expected Vandar request exception was not thrown.');
    }

    public function test_rate_limit_retry_can_ignore_retry_after_sleep_when_configured(): void
    {
        config()->set('vandar.rate_limit.respect_retry_after', false);
        $slept = [];

        PendingVandarRequest::sleepUsing(function (int $milliseconds) use (&$slept): void {
            $slept[] = $milliseconds;
        });

        Http::fake([
            'https://api.vandar.io/*' => Http::sequence()
                ->push(['message' => 'Too many requests'], 429, ['Retry-After' => '10'])
                ->push(['data' => ['ok' => true]], 200),
        ]);

        $response = $this->app->make(VandarClient::class)->get('api', '/v2/rate-limited', auth: false);

        $this->assertTrue($response->successful());
        $this->assertSame([], $slept);
        Http::assertSentCount(2);
    }

    /**
     * @param  Closure(): VandarResponse  $call
     */
    #[DataProvider('unsafeMoneyMovingCalls')]
    public function test_unsafe_money_moving_requests_are_not_retried_on_429_by_default(Closure $call): void
    {
        Http::fake([
            '*' => Http::sequence()
                ->push(['message' => 'Too many requests'], 429, ['Retry-After' => '0'])
                ->push(['ok' => true], 200),
        ]);

        $response = $call();

        $this->assertTrue($response->tooManyRequests());
        Http::assertSentCount(1);
    }

    /**
     * @return array<string, array{Closure(): VandarResponse}>
     */
    public static function unsafeMoneyMovingCalls(): array
    {
        return [
            'ipg send' => [static fn (): VandarResponse => Vandar::ipg()->send(['amount' => 100000])],
            'ipg verify' => [static fn (): VandarResponse => Vandar::ipg()->verify('fake-payment-token')],
            'refund create' => [static fn (): VandarResponse => Vandar::refunds()->create('fake-transaction-id', ['amount' => 100000])],
            'settlement create' => [static fn (): VandarResponse => Vandar::settlements()->create(['amount' => 100000, 'iban' => 'fake-iban'])],
            'settlement cancel' => [static fn (): VandarResponse => Vandar::settlements()->cancel('fake-track-id')],
            'queued settlement create' => [static fn (): VandarResponse => Vandar::queuedSettlements()->create(['amount' => 100000])],
            'queued settlement cancel' => [static fn (): VandarResponse => Vandar::queuedSettlements()->cancelById('fake-queued-id')],
            'batch settlement create' => [static fn (): VandarResponse => Vandar::batchSettlements()->create(['settlements' => [['amount' => 100000]]])],
            'avand deposit' => [static fn (): VandarResponse => Vandar::avand()->deposit(['amount' => 100000])],
            'avand suspicious payment resolution' => [static fn (): VandarResponse => Vandar::avand()->suspiciousPayment('fake-suspicious-id', ['status' => 'resolved'])],
            'subscription withdrawal create' => [static fn (): VandarResponse => Vandar::subscriptions()->createWithdrawal(['amount' => 100000])],
            'subscription withdrawal update' => [static fn (): VandarResponse => Vandar::subscriptions()->updateWithdrawal('fake-withdrawal-id', ['status' => 'canceled'])],
            'subscription refund create' => [static fn (): VandarResponse => Vandar::subscriptions()->createRefund(['withdrawal_id' => 'fake-withdrawal-id'])],
        ];
    }
}
