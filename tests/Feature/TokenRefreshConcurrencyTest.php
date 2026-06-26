<?php

declare(strict_types=1);

namespace Zarbinco\LaravelVandar\Tests\Feature;

use Carbon\CarbonImmutable;
use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\Repository as LaravelCacheRepository;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Zarbinco\LaravelVandar\Contracts\TokenStore;
use Zarbinco\LaravelVandar\DTO\TokenSet;
use Zarbinco\LaravelVandar\Exceptions\VandarTokenRefreshException;
use Zarbinco\LaravelVandar\Http\VandarClient;
use Zarbinco\LaravelVandar\Tests\TestCase;
use Zarbinco\LaravelVandar\Token\TokenManager;

final class TokenRefreshConcurrencyTest extends TestCase
{
    protected function tearDown(): void
    {
        TokenManager::sleepUsing(null);

        parent::tearDown();
    }

    public function test_valid_token_returns_without_attempting_refresh(): void
    {
        Http::fake();
        $store = new TokenRefreshTestTokenStore($this->token('fake-valid-access-token', 'fake-valid-refresh-token', 7200));
        $lock = new TokenRefreshTestLock([true]);

        $tokens = $this->manager($store, $lock)->refresh();

        $this->assertSame('fake-valid-access-token', $tokens->accessToken);
        $this->assertSame(0, $lock->getCalls);
        Http::assertNothingSent();
    }

    public function test_refresh_happens_once_when_lock_is_acquired(): void
    {
        Http::fake([
            'https://api.vandar.io/v3/refreshtoken' => Http::response([
                'token_type' => 'Bearer',
                'access_token' => 'fake-new-access-token',
                'refresh_token' => 'fake-new-refresh-token',
                'expires_in' => 432000,
            ], 200),
        ]);
        $store = new TokenRefreshTestTokenStore($this->token('fake-stale-access-token', 'fake-stale-refresh-token', 60));
        $lock = new TokenRefreshTestLock([true]);

        $tokens = $this->manager($store, $lock)->refresh();

        $this->assertSame('fake-new-access-token', $tokens->accessToken);
        $this->assertSame(1, $store->saveCalls);
        $this->assertSame(1, $lock->getCalls);
        $this->assertSame(1, $lock->releaseCalls);
        Http::assertSentCount(1);
        Http::assertSent(fn (Request $request): bool => $request->url() === 'https://api.vandar.io/v3/refreshtoken'
            && $request['refreshtoken'] === 'fake-stale-refresh-token');
    }

    public function test_waiting_for_lock_returns_token_refreshed_by_another_process(): void
    {
        config()->set('vandar.tokens.lock_wait_seconds', 1);
        config()->set('vandar.tokens.refresh_attempts', 2);
        config()->set('vandar.tokens.refresh_retry_sleep_ms', 250);
        Http::fake();

        $store = new TokenRefreshTestTokenStore($this->token('fake-stale-access-token', 'fake-stale-refresh-token', 60));
        $fresh = $this->token('fake-other-access-token', 'fake-other-refresh-token', 7200);
        $lock = new TokenRefreshTestLock([false, true]);
        $slept = [];

        TokenManager::sleepUsing(function (int $milliseconds) use ($store, $fresh, &$slept): void {
            $slept[] = $milliseconds;
            $store->tokens = $fresh;
        });

        $tokens = $this->manager($store, $lock)->refresh();

        $this->assertSame('fake-other-access-token', $tokens->accessToken);
        $this->assertSame([250], $slept);
        $this->assertSame(2, $lock->getCalls);
        Http::assertNothingSent();
    }

    public function test_unavailable_lock_retries_according_to_configured_attempts(): void
    {
        config()->set('vandar.tokens.lock_wait_seconds', 0);
        config()->set('vandar.tokens.refresh_attempts', 3);
        config()->set('vandar.tokens.refresh_retry_sleep_ms', 123);
        Http::fake();

        $store = new TokenRefreshTestTokenStore($this->token('fake-stale-access-token', 'fake-stale-refresh-token', 60));
        $lock = new TokenRefreshTestLock([false, false, false]);
        $slept = [];

        TokenManager::sleepUsing(function (int $milliseconds) use (&$slept): void {
            $slept[] = $milliseconds;
        });

        try {
            $this->manager($store, $lock)->refresh();
        } catch (VandarTokenRefreshException $exception) {
            $context = $exception->context();

            $this->assertSame(3, $lock->getCalls);
            $this->assertSame([123, 123], $slept);
            $this->assertSame(3, $context['attempts'] ?? null);
            $this->assertSame(0, $context['lock_wait_seconds'] ?? null);
            Http::assertNothingSent();

            return;
        }

        $this->fail('Expected Vandar token refresh exception was not thrown.');
    }

    public function test_exhausted_refresh_attempt_exception_is_safe(): void
    {
        config()->set('vandar.tokens.lock_wait_seconds', 0);
        config()->set('vandar.tokens.refresh_attempts', 2);
        config()->set('vandar.tokens.refresh_retry_sleep_ms', 0);
        Http::fake();

        $store = new TokenRefreshTestTokenStore($this->token('fake-secret-access-token', 'fake-secret-refresh-token', 60));
        $lock = new TokenRefreshTestLock([false, false]);

        try {
            $this->manager($store, $lock)->refresh();
        } catch (VandarTokenRefreshException $exception) {
            $context = json_encode($exception->context());

            $this->assertIsString($context);
            $this->assertStringNotContainsString('fake-secret-access-token', $exception->getMessage());
            $this->assertStringNotContainsString('fake-secret-refresh-token', $exception->getMessage());
            $this->assertStringNotContainsString('fake-secret-access-token', $context);
            $this->assertStringNotContainsString('fake-secret-refresh-token', $context);

            return;
        }

        $this->fail('Expected Vandar token refresh exception was not thrown.');
    }

    private function manager(TokenRefreshTestTokenStore $store, TokenRefreshTestLock $lock): TokenManager
    {
        return new TokenManager(
            store: $store,
            client: $this->app->make(VandarClient::class),
            config: $this->app['config'],
            cache: new TokenRefreshTestCacheRepository($lock),
        );
    }

    private function token(string $accessToken, string $refreshToken, int $expiresIn): TokenSet
    {
        return new TokenSet(
            tokenType: 'Bearer',
            accessToken: $accessToken,
            refreshToken: $refreshToken,
            expiresIn: $expiresIn,
            expiresAt: CarbonImmutable::now()->addSeconds($expiresIn),
        );
    }
}

final class TokenRefreshTestTokenStore implements TokenStore
{
    public int $saveCalls = 0;

    public function __construct(
        public ?TokenSet $tokens,
    ) {}

    public function accessToken(): ?string
    {
        return $this->tokens?->accessToken;
    }

    public function refreshToken(): ?string
    {
        return $this->tokens?->refreshToken;
    }

    public function tokens(): ?TokenSet
    {
        return $this->tokens;
    }

    public function save(TokenSet $tokens): void
    {
        $this->saveCalls++;
        $this->tokens = $tokens;
    }

    public function clear(): void
    {
        $this->tokens = null;
    }
}

final class TokenRefreshTestCacheRepository extends LaravelCacheRepository
{
    public function __construct(
        private readonly TokenRefreshTestLock $testLock,
    ) {
        parent::__construct(new ArrayStore);
    }

    public function lock(string $name, int $seconds): TokenRefreshTestLock
    {
        return $this->testLock;
    }
}

final class TokenRefreshTestLock
{
    public int $getCalls = 0;

    public int $releaseCalls = 0;

    /**
     * @param  array<int, bool>  $results
     */
    public function __construct(
        private array $results,
    ) {}

    public function get(): bool
    {
        $this->getCalls++;

        return array_shift($this->results) ?? false;
    }

    public function release(): void
    {
        $this->releaseCalls++;
    }
}
