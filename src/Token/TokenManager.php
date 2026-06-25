<?php

declare(strict_types=1);

namespace Zarbinco\LaravelVandar\Token;

use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Zarbinco\LaravelVandar\Contracts\TokenStore;
use Zarbinco\LaravelVandar\DTO\TokenSet;
use Zarbinco\LaravelVandar\Exceptions\VandarTokenException;
use Zarbinco\LaravelVandar\Exceptions\VandarTokenRefreshException;
use Zarbinco\LaravelVandar\Exceptions\VandarTokenUnavailableException;
use Zarbinco\LaravelVandar\Http\VandarClient;

final class TokenManager
{
    public function __construct(
        private readonly TokenStore $store,
        private readonly VandarClient $client,
        private readonly ConfigRepository $config,
        private readonly CacheRepository $cache,
    ) {}

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
        if (! method_exists($this->cache, 'lock')) {
            return $this->performRefresh($tokens);
        }

        $lock = $this->cache->lock($this->lockKey(), $this->lockSeconds());

        if ($lock->get()) {
            try {
                return $this->performRefresh($tokens);
            } finally {
                $lock->release();
            }
        }

        usleep(200000);

        $current = $this->current();

        if ($current !== null && ! $current->shouldRefresh(refreshBeforeSeconds: $this->refreshBeforeSeconds())) {
            return $current;
        }

        throw new VandarTokenRefreshException(
            message: $force
                ? 'Unable to acquire Vandar token refresh lock.'
                : 'Unable to acquire Vandar token refresh lock and current token still needs refresh.',
        );
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
        } catch (\Throwable $exception) {
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
}
