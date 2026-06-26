<?php

declare(strict_types=1);

namespace Zarbinco\LaravelVandar\Token;

use Closure;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Throwable;
use Zarbinco\LaravelVandar\Contracts\TokenStore;
use Zarbinco\LaravelVandar\DTO\TokenSet;
use Zarbinco\LaravelVandar\Exceptions\VandarTokenException;
use Zarbinco\LaravelVandar\Exceptions\VandarTokenRefreshException;
use Zarbinco\LaravelVandar\Exceptions\VandarTokenUnavailableException;
use Zarbinco\LaravelVandar\Http\VandarClient;

final class TokenManager
{
    /**
     * @var Closure(int): void|null
     */
    private static ?Closure $sleepUsing = null;

    public function __construct(
        private readonly TokenStore $store,
        private readonly VandarClient $client,
        private readonly ConfigRepository $config,
        private readonly CacheRepository $cache,
    ) {}

    /**
     * @internal
     */
    public static function sleepUsing(?callable $sleeper): void
    {
        self::$sleepUsing = $sleeper === null ? null : Closure::fromCallable($sleeper);
    }

    public function current(): ?TokenSet
    {
        if (method_exists($this->store, 'tokens')) {
            $tokens = $this->store->tokens();

            if ($tokens instanceof TokenSet) {
                return $tokens;
            }
        }

        return TokenSet::fromConfig(
            $this->store->accessToken(),
            $this->store->refreshToken(),
            $this->accessTokenTtlSeconds(),
        );
    }

    public function accessToken(): ?string
    {
        return $this->current()?->accessToken;
    }

    public function authorizationHeader(): ?string
    {
        return $this->current()?->authorizationHeader();
    }

    public function hasToken(): bool
    {
        return $this->accessToken() !== null;
    }

    public function shouldRefresh(): bool
    {
        return $this->current()?->shouldRefresh(refreshBeforeSeconds: $this->refreshBeforeSeconds()) ?? false;
    }

    public function refresh(bool $force = false): TokenSet
    {
        $tokens = $this->current();

        if ($tokens === null || $tokens->refreshToken === '') {
            throw new VandarTokenUnavailableException('No Vandar refresh token is available.');
        }

        if (! $force && ! $tokens->shouldRefresh(refreshBeforeSeconds: $this->refreshBeforeSeconds())) {
            return $tokens;
        }

        return $this->refreshWithOptionalLock($tokens, $force);
    }

    public function clear(): void
    {
        $this->store->clear();
    }

    private function refreshWithOptionalLock(TokenSet $tokens, bool $force): TokenSet
    {
        $lastException = null;
        $attempts = $this->refreshAttempts();

        for ($attempt = 1; $attempt <= $attempts; $attempt++) {
            $current = $attempt === 1 ? $tokens : $this->current();

            if ($current === null || $current->refreshToken === '') {
                throw new VandarTokenUnavailableException('No Vandar refresh token is available.');
            }

            if ($this->hasUsableToken($current, $tokens, $force)) {
                return $current;
            }

            $lock = $this->refreshLock();

            if ($lock === null) {
                try {
                    return $this->performRefresh($current);
                } catch (VandarTokenRefreshException $exception) {
                    $lastException = $exception;
                    $this->sleepBeforeNextRefreshAttempt($attempt, $attempts);

                    continue;
                }
            }

            if ($this->acquireLock($lock)) {
                try {
                    $lockedCurrent = $this->current() ?? $current;

                    if ($this->hasUsableToken($lockedCurrent, $tokens, $force)) {
                        return $lockedCurrent;
                    }

                    return $this->performRefresh($lockedCurrent);
                } catch (VandarTokenRefreshException $exception) {
                    $lastException = $exception;
                } finally {
                    $this->releaseLock($lock);
                }

                $this->sleepBeforeNextRefreshAttempt($attempt, $attempts);

                continue;
            }

            $this->waitForLock($lock);

            $current = $this->current();

            if ($current !== null && $this->hasUsableToken($current, $tokens, $force)) {
                return $current;
            }

            $this->sleepBeforeNextRefreshAttempt($attempt, $attempts);
        }

        throw $this->exhaustedRefreshAttemptsException($lastException);
    }

    private function performRefresh(TokenSet $tokens): TokenSet
    {
        $response = $this->client->post(
            base: 'api',
            path: '/v3/refreshtoken',
            payload: ['refreshtoken' => $tokens->refreshToken],
            auth: false,
        );

        if ($response->failed()) {
            throw new VandarTokenRefreshException(
                message: 'Vandar token refresh request failed.',
                status: $response->status(),
                response: $response->toArray(),
            );
        }

        try {
            $refreshed = TokenSet::fromArray($response->json());
        } catch (VandarTokenException $exception) {
            throw new VandarTokenRefreshException(
                message: 'Vandar token refresh response did not include usable token data.',
                status: $response->status(),
                response: $response->toArray(),
                previous: $exception,
            );
        }

        try {
            $this->store->save($refreshed);
        } catch (VandarTokenRefreshException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            throw new VandarTokenRefreshException(
                message: 'Unable to store refreshed Vandar token data.',
                status: 0,
                response: $refreshed->redactedArray(),
                previous: $exception,
            );
        }

        return $refreshed;
    }

    private function accessTokenTtlSeconds(): ?int
    {
        $ttl = $this->config->get('vandar.tokens.access_token_ttl_seconds');

        return is_numeric($ttl) ? (int) $ttl : null;
    }

    private function refreshBeforeSeconds(): int
    {
        $seconds = $this->config->get('vandar.tokens.refresh_before_expiration_seconds', 3600);

        return is_numeric($seconds) ? (int) $seconds : 3600;
    }

    private function lockKey(): string
    {
        $key = $this->config->get('vandar.tokens.lock_key', 'vandar.tokens.refresh.lock');

        return is_string($key) && $key !== '' ? $key : 'vandar.tokens.refresh.lock';
    }

    private function lockSeconds(): int
    {
        $seconds = $this->config->get('vandar.tokens.lock_seconds', 30);

        return is_numeric($seconds) ? max(1, (int) $seconds) : 30;
    }

    private function lockWaitSeconds(): int
    {
        return $this->intConfig('vandar.tokens.lock_wait_seconds', 5, minimum: 0);
    }

    private function refreshAttempts(): int
    {
        return $this->intConfig('vandar.tokens.refresh_attempts', 3, minimum: 1);
    }

    private function refreshRetrySleepMilliseconds(): int
    {
        return $this->intConfig('vandar.tokens.refresh_retry_sleep_ms', 250, minimum: 0);
    }

    private function intConfig(string $key, int $default, int $minimum): int
    {
        $value = $this->config->get($key, $default);
        $value = is_numeric($value) ? (int) $value : $default;

        return max($minimum, $value);
    }

    private function hasUsableToken(TokenSet $current, TokenSet $original, bool $force): bool
    {
        if ($current->shouldRefresh(refreshBeforeSeconds: $this->refreshBeforeSeconds())) {
            return false;
        }

        if (! $force) {
            return true;
        }

        return $current->accessToken !== $original->accessToken
            || $current->refreshToken !== $original->refreshToken;
    }

    private function refreshLock(): mixed
    {
        if (is_callable([$this->cache, 'lock'])) {
            return $this->cache->lock($this->lockKey(), $this->lockSeconds());
        }

        $store = $this->cache->getStore();

        if (is_callable([$store, 'lock'])) {
            return $store->lock($this->lockKey(), $this->lockSeconds());
        }

        return null;
    }

    private function acquireLock(mixed $lock): bool
    {
        try {
            return is_object($lock) && is_callable([$lock, 'get']) && (bool) $lock->get();
        } catch (Throwable) {
            return false;
        }
    }

    private function releaseLock(mixed $lock): void
    {
        if (! is_object($lock) || ! is_callable([$lock, 'release'])) {
            return;
        }

        try {
            $lock->release();
        } catch (Throwable) {
            //
        }
    }

    private function waitForLock(mixed $lock): void
    {
        $waitSeconds = $this->lockWaitSeconds();

        if ($waitSeconds <= 0) {
            return;
        }

        if (is_object($lock) && is_callable([$lock, 'block'])) {
            try {
                if ((bool) $lock->block($waitSeconds)) {
                    $this->releaseLock($lock);
                }

                return;
            } catch (Throwable) {
                return;
            }
        }

        $waitMilliseconds = $waitSeconds * 1000;
        $sleepMilliseconds = max(50, $this->refreshRetrySleepMilliseconds());
        $sleptMilliseconds = 0;

        while ($sleptMilliseconds < $waitMilliseconds) {
            $sleepFor = min($sleepMilliseconds, $waitMilliseconds - $sleptMilliseconds);
            $this->sleepMilliseconds($sleepFor);
            $sleptMilliseconds += $sleepFor;

            if ($this->acquireLock($lock)) {
                $this->releaseLock($lock);

                return;
            }
        }
    }

    private function sleepBeforeNextRefreshAttempt(int $attempt, int $attempts): void
    {
        if ($attempt >= $attempts) {
            return;
        }

        $this->sleepMilliseconds($this->refreshRetrySleepMilliseconds());
    }

    private function sleepMilliseconds(int $milliseconds): void
    {
        if ($milliseconds <= 0) {
            return;
        }

        if (self::$sleepUsing instanceof Closure) {
            (self::$sleepUsing)($milliseconds);

            return;
        }

        usleep($milliseconds * 1000);
    }

    private function exhaustedRefreshAttemptsException(?VandarTokenRefreshException $previous): VandarTokenRefreshException
    {
        return new VandarTokenRefreshException(
            message: 'Unable to refresh Vandar token after configured attempts.',
            status: $previous?->getCode() ?? 0,
            response: $previous?->context()['response'] ?? [],
            previous: $previous,
            context: [
                'attempts' => $this->refreshAttempts(),
                'lock_wait_seconds' => $this->lockWaitSeconds(),
            ],
        );
    }
}
