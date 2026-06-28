<?php

declare(strict_types=1);

namespace Zarbinco\LaravelVandar\Tests\Feature;

use Carbon\CarbonImmutable;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Zarbinco\LaravelVandar\Exceptions\VandarTokenRefreshException;
use Zarbinco\LaravelVandar\Http\VandarClient;
use Zarbinco\LaravelVandar\Tests\TestCase;
use Zarbinco\LaravelVandar\Token\TokenManager;

final class TokenAutoRefreshTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();

        config()->set('vandar.tokens.store', 'cache');
        config()->set('vandar.tokens.cache_key', 'vandar.auto-refresh-test.tokens');
        config()->set('vandar.tokens.access_token', 'fake-current-access-token');
        config()->set('vandar.tokens.refresh_token', 'fake-current-refresh-token');
        config()->set('vandar.tokens.auto_refresh', false);
    }

    public function test_pending_request_uses_current_token_and_does_not_refresh_expiring_token_by_default(): void
    {
        config()->set('vandar.tokens.access_token_ttl_seconds', 60);

        Http::fake([
            'https://api.vandar.io/v3/refreshtoken' => Http::response([
                'access_token' => 'fake-refreshed-access-token',
                'refresh_token' => 'fake-refreshed-refresh-token',
            ], 200),
            'https://api.vandar.io/v2/manual*' => Http::response(['ok' => true], 200),
        ]);

        $this->app->make(VandarClient::class)->get('api', '/v2/manual');

        Http::assertSentCount(1);
        Http::assertSent(fn (Request $request): bool => $request->url() === 'https://api.vandar.io/v2/manual'
            && $request->hasHeader('Authorization', 'Bearer fake-current-access-token'));
    }

    public function test_auto_refresh_disabled_does_not_refresh_expired_config_fallback_timestamp(): void
    {
        config()->set('vandar.tokens.access_token_ttl_seconds', 7200);
        config()->set('vandar.tokens.access_token_expires_at', CarbonImmutable::now()->subMinute()->toIso8601String());

        Http::fake([
            'https://api.vandar.io/v3/refreshtoken' => Http::response([
                'access_token' => 'fake-refreshed-access-token',
                'refresh_token' => 'fake-refreshed-refresh-token',
            ], 200),
            'https://api.vandar.io/v2/manual*' => Http::response(['ok' => true], 200),
        ]);

        $this->app->make(VandarClient::class)->get('api', '/v2/manual');

        Http::assertSentCount(1);
        Http::assertSent(fn (Request $request): bool => $request->url() === 'https://api.vandar.io/v2/manual'
            && $request->hasHeader('Authorization', 'Bearer fake-current-access-token'));
    }

    public function test_existing_authorization_header_method_does_not_auto_refresh(): void
    {
        config()->set('vandar.tokens.auto_refresh', true);
        config()->set('vandar.tokens.access_token_ttl_seconds', 60);

        Http::fake([
            'https://api.vandar.io/v3/refreshtoken' => Http::response([
                'access_token' => 'fake-refreshed-access-token',
                'refresh_token' => 'fake-refreshed-refresh-token',
            ], 200),
        ]);

        $header = $this->app->make(TokenManager::class)->authorizationHeader();

        $this->assertSame('Bearer fake-current-access-token', $header);
        Http::assertNothingSent();
    }

    public function test_request_authorization_method_uses_existing_behavior_when_auto_refresh_is_disabled(): void
    {
        config()->set('vandar.tokens.access_token_ttl_seconds', 60);

        Http::fake([
            'https://api.vandar.io/v3/refreshtoken' => Http::response([
                'access_token' => 'fake-refreshed-access-token',
                'refresh_token' => 'fake-refreshed-refresh-token',
            ], 200),
        ]);

        $header = $this->app->make(TokenManager::class)->authorizationHeaderForRequest();

        $this->assertSame('Bearer fake-current-access-token', $header);
        Http::assertNothingSent();
    }

    public function test_request_authorization_method_does_not_refresh_fresh_token_when_enabled(): void
    {
        config()->set('vandar.tokens.auto_refresh', true);
        config()->set('vandar.tokens.access_token_ttl_seconds', 7200);

        Http::fake();

        $header = $this->app->make(TokenManager::class)->authorizationHeaderForRequest();

        $this->assertSame('Bearer fake-current-access-token', $header);
        Http::assertNothingSent();
    }

    public function test_request_authorization_method_does_not_refresh_fresh_config_expiry_when_enabled(): void
    {
        config()->set('vandar.tokens.auto_refresh', true);
        config()->set('vandar.tokens.access_token_ttl_seconds', 60);
        config()->set('vandar.tokens.access_token_expires_at', CarbonImmutable::now()->addHours(2)->toIso8601String());

        Http::fake();

        $header = $this->app->make(TokenManager::class)->authorizationHeaderForRequest();

        $this->assertSame('Bearer fake-current-access-token', $header);
        Http::assertNothingSent();
    }

    public function test_request_authorization_method_refreshes_expiring_token_when_enabled(): void
    {
        config()->set('vandar.tokens.auto_refresh', true);
        config()->set('vandar.tokens.access_token_ttl_seconds', 60);

        Http::fake([
            'https://api.vandar.io/v3/refreshtoken' => Http::response([
                'token_type' => 'Bearer',
                'access_token' => 'fake-refreshed-access-token',
                'refresh_token' => 'fake-refreshed-refresh-token',
                'expires_in' => 7200,
            ], 200),
        ]);

        $header = $this->app->make(TokenManager::class)->authorizationHeaderForRequest();

        $this->assertSame('Bearer fake-refreshed-access-token', $header);
        Http::assertSentCount(1);
        Http::assertSent(fn (Request $request): bool => $request->url() === 'https://api.vandar.io/v3/refreshtoken'
            && ! $request->hasHeader('Authorization')
            && $request['refreshtoken'] === 'fake-current-refresh-token');
    }

    public function test_request_authorization_method_refreshes_expired_config_expiry_when_enabled(): void
    {
        config()->set('vandar.tokens.auto_refresh', true);
        config()->set('vandar.tokens.access_token_ttl_seconds', 7200);
        config()->set('vandar.tokens.access_token_expires_at', CarbonImmutable::now()->subMinute()->toIso8601String());

        Http::fake([
            'https://api.vandar.io/v3/refreshtoken' => Http::response([
                'token_type' => 'Bearer',
                'access_token' => 'fake-refreshed-access-token',
                'refresh_token' => 'fake-refreshed-refresh-token',
                'expires_in' => 7200,
            ], 200),
        ]);

        $header = $this->app->make(TokenManager::class)->authorizationHeaderForRequest();

        $this->assertSame('Bearer fake-refreshed-access-token', $header);
        Http::assertSentCount(1);
        Http::assertSent(fn (Request $request): bool => $request->url() === 'https://api.vandar.io/v3/refreshtoken'
            && $request['refreshtoken'] === 'fake-current-refresh-token');
    }

    public function test_pending_request_uses_refreshed_authorization_header_when_auto_refresh_is_enabled(): void
    {
        config()->set('vandar.tokens.auto_refresh', true);
        config()->set('vandar.tokens.access_token_ttl_seconds', 60);

        Http::fake([
            'https://api.vandar.io/v3/refreshtoken' => Http::response([
                'token_type' => 'Bearer',
                'access_token' => 'fake-refreshed-access-token',
                'refresh_token' => 'fake-refreshed-refresh-token',
                'expires_in' => 7200,
            ], 200),
            'https://api.vandar.io/v2/manual*' => Http::response(['ok' => true], 200),
        ]);

        $this->app->make(VandarClient::class)->get('api', '/v2/manual');

        Http::assertSentCount(2);
        Http::assertSent(fn (Request $request): bool => $request->url() === 'https://api.vandar.io/v3/refreshtoken'
            && ! $request->hasHeader('Authorization'));
        Http::assertSent(fn (Request $request): bool => $request->url() === 'https://api.vandar.io/v2/manual'
            && $request->hasHeader('Authorization', 'Bearer fake-refreshed-access-token'));
    }

    public function test_auto_refresh_handles_missing_access_token_with_available_refresh_token(): void
    {
        config()->set('vandar.tokens.auto_refresh', true);
        config()->set('vandar.tokens.access_token', null);
        config()->set('vandar.tokens.refresh_token', 'fake-refresh-only-token');

        Http::fake([
            'https://api.vandar.io/v3/refreshtoken' => Http::response([
                'token_type' => 'Bearer',
                'access_token' => 'fake-restored-access-token',
                'refresh_token' => 'fake-restored-refresh-token',
                'expires_in' => 7200,
            ], 200),
        ]);

        $manager = $this->app->make(TokenManager::class);
        $header = $manager->authorizationHeaderForRequest();

        $this->assertSame('Bearer fake-restored-access-token', $header);
        $this->assertSame('fake-restored-access-token', $manager->accessToken());
        Http::assertSentCount(1);
        Http::assertSent(fn (Request $request): bool => $request->url() === 'https://api.vandar.io/v3/refreshtoken'
            && $request['refreshtoken'] === 'fake-refresh-only-token');
    }

    public function test_auto_refresh_preserves_no_token_request_behavior(): void
    {
        config()->set('vandar.tokens.auto_refresh', true);
        config()->set('vandar.tokens.access_token', null);
        config()->set('vandar.tokens.refresh_token', null);

        Http::fake([
            'https://api.vandar.io/v2/manual*' => Http::response(['ok' => true], 200),
        ]);

        $this->assertNull($this->app->make(TokenManager::class)->authorizationHeaderForRequest());

        $this->app->make(VandarClient::class)->get('api', '/v2/manual');

        Http::assertSentCount(1);
        Http::assertSent(fn (Request $request): bool => $request->url() === 'https://api.vandar.io/v2/manual'
            && ! $request->hasHeader('Authorization'));
    }

    public function test_refresh_failure_during_request_authorization_is_explicit(): void
    {
        config()->set('vandar.tokens.auto_refresh', true);
        config()->set('vandar.tokens.access_token_ttl_seconds', 60);
        config()->set('vandar.tokens.refresh_attempts', 1);

        Http::fake([
            'https://api.vandar.io/v3/refreshtoken' => Http::response(['message' => 'Unauthorized'], 401),
            'https://api.vandar.io/v2/manual*' => Http::response(['ok' => true], 200),
        ]);

        try {
            $this->app->make(VandarClient::class)->get('api', '/v2/manual');
        } catch (VandarTokenRefreshException $exception) {
            $this->assertSame('Unable to refresh Vandar token.', $exception->getMessage());
            $this->assertSame(401, $exception->getCode());
            Http::assertSentCount(1);

            return;
        }

        $this->fail('Expected Vandar token refresh exception was not thrown.');
    }

    public function test_auto_refresh_does_not_retry_original_request_after_failed_api_authorization(): void
    {
        config()->set('vandar.tokens.auto_refresh', true);
        config()->set('vandar.tokens.access_token_ttl_seconds', 60);

        Http::fake([
            'https://api.vandar.io/v3/refreshtoken' => Http::response([
                'token_type' => 'Bearer',
                'access_token' => 'fake-refreshed-access-token',
                'refresh_token' => 'fake-refreshed-refresh-token',
                'expires_in' => 7200,
            ], 200),
            'https://api.vandar.io/v2/manual*' => Http::response(['message' => 'Unauthorized'], 401),
        ]);

        $response = $this->app->make(VandarClient::class)->get('api', '/v2/manual');

        $this->assertSame(401, $response->status());
        Http::assertSentCount(2);
        Http::assertSent(fn (Request $request): bool => $request->url() === 'https://api.vandar.io/v3/refreshtoken');
        Http::assertSent(fn (Request $request): bool => $request->url() === 'https://api.vandar.io/v2/manual'
            && $request->hasHeader('Authorization', 'Bearer fake-refreshed-access-token'));
    }
}
