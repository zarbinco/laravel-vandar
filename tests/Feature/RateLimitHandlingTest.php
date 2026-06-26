<?php

declare(strict_types=1);

namespace Zarbinco\LaravelVandar\Tests\Feature;

use Illuminate\Support\Facades\Http;
use Zarbinco\LaravelVandar\Facades\Vandar;
use Zarbinco\LaravelVandar\Http\PendingVandarRequest;
use Zarbinco\LaravelVandar\Http\VandarClient;
use Zarbinco\LaravelVandar\Tests\TestCase;

final class RateLimitHandlingTest extends TestCase
{
    protected function tearDown(): void
    {
        PendingVandarRequest::sleepUsing(null);

        parent::tearDown();
    }

    public function test_get_requests_retry_once_on_429_when_configured(): void
    {
        config()->set('vandar.rate_limit.max_retry_after_seconds', 0);
        Http::fake([
            'https://api.vandar.io/*' => Http::sequence()
                ->push(['message' => 'Too many requests'], 429, ['Retry-After' => '2'])
                ->push(['data' => ['ok' => true]], 200),
        ]);

        $response = $this->app->make(VandarClient::class)->get('api', '/v2/rate-limited', auth: false);

        $this->assertTrue($response->successful());
        $this->assertSame(['ok' => true], $response->data());
        Http::assertSentCount(2);
    }

    public function test_post_requests_do_not_retry_on_429_by_default(): void
    {
        Http::fake([
            'https://api.vandar.io/*' => Http::sequence()
                ->push(['message' => 'Too many requests'], 429, ['Retry-After' => '0'])
                ->push(['data' => ['ok' => true]], 200),
        ]);

        $response = $this->app->make(VandarClient::class)->post('api', '/v2/manual', ['field' => 'value'], auth: false);

        $this->assertTrue($response->tooManyRequests());
        Http::assertSentCount(1);
    }

    public function test_money_moving_post_requests_do_not_retry_on_429_by_default(): void
    {
        config()->set('vandar.ipg.api_key', 'fake-ipg-api-key');
        Http::fake([
            'https://ipg.vandar.io/*' => Http::sequence()
                ->push(['message' => 'Too many requests'], 429, ['Retry-After' => '0'])
                ->push(['status' => 'OK'], 200),
        ]);

        $response = Vandar::ipg()->verify('fake-payment-token');

        $this->assertTrue($response->tooManyRequests());
        Http::assertSentCount(1);
    }

    public function test_money_moving_post_retry_requires_explicit_opt_in(): void
    {
        config()->set('vandar.ipg.api_key', 'fake-ipg-api-key');
        config()->set('vandar.rate_limit.retry_money_moving_requests', true);
        config()->set('vandar.rate_limit.max_retry_after_seconds', 0);
        Http::fake([
            'https://ipg.vandar.io/*' => Http::sequence()
                ->push(['message' => 'Too many requests'], 429, ['Retry-After' => '1'])
                ->push(['status' => 'OK'], 200),
        ]);

        $response = Vandar::ipg()->verify('fake-payment-token');

        $this->assertTrue($response->successful());
        Http::assertSentCount(2);
    }

    public function test_max_retry_after_seconds_caps_delay_without_real_sleeping(): void
    {
        config()->set('vandar.rate_limit.max_retry_after_seconds', 2);
        Http::fake([
            'https://api.vandar.io/*' => Http::sequence()
                ->push(['message' => 'Too many requests'], 429, ['Retry-After' => '10'])
                ->push(['data' => ['ok' => true]], 200),
        ]);
        $slept = [];

        PendingVandarRequest::sleepUsing(function (int $milliseconds) use (&$slept): void {
            $slept[] = $milliseconds;
        });

        $response = $this->app->make(VandarClient::class)->get('api', '/v2/rate-limited', auth: false);

        $this->assertTrue($response->successful());
        $this->assertSame([2000], $slept);
        Http::assertSentCount(2);
    }

    public function test_rate_limit_awareness_can_be_disabled(): void
    {
        config()->set('vandar.rate_limit.aware', false);
        Http::fake([
            'https://api.vandar.io/*' => Http::sequence()
                ->push(['message' => 'Too many requests'], 429, ['Retry-After' => '0'])
                ->push(['data' => ['ok' => true]], 200),
        ]);

        $response = $this->app->make(VandarClient::class)->get('api', '/v2/rate-limited', auth: false);

        $this->assertTrue($response->tooManyRequests());
        Http::assertSentCount(1);
    }
}
