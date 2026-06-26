<?php

declare(strict_types=1);

namespace Zarbinco\LaravelVandar\Tests\Feature;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Zarbinco\LaravelVandar\Exceptions\VandarTokenRefreshException;
use Zarbinco\LaravelVandar\Exceptions\VandarTokenUnavailableException;
use Zarbinco\LaravelVandar\Tests\TestCase;
use Zarbinco\LaravelVandar\Token\TokenManager;

final class TokenManagerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        config()->set('vandar.tokens.store', 'cache');
        config()->set('vandar.tokens.access_token', 'fake-initial-access-token');
        config()->set('vandar.tokens.refresh_token', 'fake-initial-refresh-token');
        config()->set('vandar.tokens.cache_key', 'vandar.test.tokens');
    }

    public function test_refresh_posts_to_refresh_endpoint_and_stores_new_tokens(): void
    {
        Http::fake([
            'https://api.vandar.io/v3/refreshtoken' => Http::response([
                'token_type' => 'Bearer',
                'access_token' => 'fake-new-access-token',
                'refresh_token' => 'fake-new-refresh-token',
                'expires_in' => 7200,
            ], 200),
        ]);

        $tokens = $this->app->make(TokenManager::class)->refresh(force: true);

        $this->assertSame('fake-new-access-token', $tokens->accessToken);
        $this->assertSame('fake-new-access-token', $this->app->make(TokenManager::class)->accessToken());

        Http::assertSent(fn (Request $request): bool => $request->method() === 'POST'
            && $request->url() === 'https://api.vandar.io/v3/refreshtoken'
            && $request['refreshtoken'] === 'fake-initial-refresh-token'
            && ! $request->hasHeader('Authorization'));
    }

    public function test_refresh_without_force_reuses_valid_access_token(): void
    {
        Http::fake();

        $tokens = $this->app->make(TokenManager::class)->refresh();

        $this->assertSame('fake-initial-access-token', $tokens->accessToken);
        $this->assertSame('fake-initial-refresh-token', $tokens->refreshToken);
        Http::assertNothingSent();
    }

    public function test_stale_access_token_refreshes_without_force_and_stores_new_tokens(): void
    {
        config()->set('vandar.tokens.access_token_ttl_seconds', 60);

        Http::fake([
            'https://api.vandar.io/v3/refreshtoken' => Http::response([
                'token_type' => 'Bearer',
                'access_token' => 'fake-refreshed-access-token',
                'refresh_token' => 'fake-refreshed-refresh-token',
                'expires_in' => 7200,
            ], 200),
        ]);

        $tokens = $this->app->make(TokenManager::class)->refresh();

        $this->assertSame('fake-refreshed-access-token', $tokens->accessToken);
        $this->assertSame('fake-refreshed-access-token', $this->app->make(TokenManager::class)->accessToken());
        $this->assertSame('fake-refreshed-refresh-token', $this->app->make(TokenManager::class)->current()?->refreshToken);
        Http::assertSentCount(1);
    }

    public function test_refresh_logging_redacts_raw_token_values(): void
    {
        config()->set('vandar.logging.enabled', true);
        $logger = Log::spy();

        Http::fake([
            'https://api.vandar.io/v3/refreshtoken' => Http::response([
                'access_token' => 'fake-new-access-token',
                'refresh_token' => 'fake-new-refresh-token',
            ], 200),
        ]);

        $this->app->make(TokenManager::class)->refresh(force: true);

        $logger->shouldHaveReceived('debug')
            ->once()
            ->withArgs(function (string $message, array $context): bool {
                $encoded = json_encode($context);

                return $message === 'Vandar HTTP request'
                    && is_string($encoded)
                    && ! str_contains($encoded, 'fake-initial-refresh-token')
                    && ! str_contains($encoded, 'fake-new-access-token')
                    && ! str_contains($encoded, 'fake-new-refresh-token');
            });
    }

    public function test_missing_refresh_token_throws(): void
    {
        config()->set('vandar.tokens.refresh_token', null);

        $this->expectException(VandarTokenUnavailableException::class);

        $this->app->make(TokenManager::class)->refresh(force: true);
    }

    public function test_missing_token_state_is_predictable(): void
    {
        config()->set('vandar.tokens.access_token', null);
        config()->set('vandar.tokens.refresh_token', null);

        $manager = $this->app->make(TokenManager::class);

        $this->assertNull($manager->current());
        $this->assertNull($manager->accessToken());
        $this->assertNull($manager->authorizationHeader());
        $this->assertFalse($manager->hasToken());
        $this->assertFalse($manager->shouldRefresh());
    }

    public function test_invalid_refresh_response_throws_refresh_exception(): void
    {
        Http::fake([
            'https://api.vandar.io/v3/refreshtoken' => Http::response(['message' => 'ok'], 200),
        ]);

        $this->expectException(VandarTokenRefreshException::class);

        $this->app->make(TokenManager::class)->refresh(force: true);
    }

    public function test_failed_refresh_response_has_clear_exception_status_and_context(): void
    {
        Http::fake([
            'https://api.vandar.io/v3/refreshtoken' => Http::response([
                'message' => 'Unauthorized',
            ], 401),
        ]);

        try {
            $this->app->make(TokenManager::class)->refresh(force: true);
        } catch (VandarTokenRefreshException $exception) {
            $context = $exception->context();

            $this->assertSame('Unable to refresh Vandar token.', $exception->getMessage());
            $this->assertSame(401, $exception->getCode());
            $this->assertSame(401, $context['response']['status'] ?? null);
            $this->assertSame('Unauthorized', $context['response']['json']['message'] ?? null);

            return;
        }

        $this->fail('Expected Vandar token refresh exception was not thrown.');
    }
}
