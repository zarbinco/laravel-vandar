<?php

declare(strict_types=1);

namespace Zarbinco\LaravelVandar\Token;

use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Contracts\Encryption\StringEncrypter;
use JsonException;
use Zarbinco\LaravelVandar\Contracts\TokenStore;
use Zarbinco\LaravelVandar\DTO\TokenSet;

final class CacheTokenStore implements TokenStore
{
    public function __construct(
        private readonly CacheRepository $cache,
        private readonly ConfigRepository $config,
        private readonly StringEncrypter $encrypter,
    ) {}

    public function accessToken(): ?string
    {
        return $this->tokens()?->accessToken;
    }

    public function refreshToken(): ?string
    {
        return $this->tokens()?->refreshToken;
    }

    public function tokens(): ?TokenSet
    {
        $payload = $this->cachedPayload();

        if ($payload !== null) {
            try {
                return TokenSet::fromArray($payload);
            } catch (\Throwable) {
                return $this->fallbackTokens();
            }
        }

        return $this->fallbackTokens(persist: $this->shouldPersistConfigFallbackToCache());
    }

    public function save(TokenSet $tokens): void
    {
        $payload = $tokens->toArray();
        $value = $this->shouldEncryptCache()
            ? $this->encrypter->encryptString(json_encode($payload, JSON_THROW_ON_ERROR))
            : $payload;

        $this->cache->put($this->cacheKey(), $value, $this->cacheTtlSeconds());
    }

    public function clear(): void
    {
        $this->cache->forget($this->cacheKey());
    }

    /**
     * @return array<string, mixed>|null
     */
    private function cachedPayload(): ?array
    {
        $value = $this->cache->get($this->cacheKey());

        if ($value === null) {
            return null;
        }

        if (is_array($value)) {
            return $value;
        }

        if (! is_string($value)) {
            return null;
        }

        try {
            $json = $this->shouldEncryptCache()
                ? $this->encrypter->decryptString($value)
                : $value;

            $payload = json_decode($json, true, flags: JSON_THROW_ON_ERROR);

            return is_array($payload) ? $payload : null;
        } catch (DecryptException|JsonException) {
            return null;
        }
    }

    private function fallbackTokens(bool $persist = false): ?TokenSet
    {
        $tokens = TokenSet::fromConfigWithExpiry(
            $this->configToken('access_token'),
            $this->configToken('refresh_token'),
            $this->accessTokenTtlSeconds(),
            $this->accessTokenExpiresAt(),
        );

        if ($persist && $tokens !== null) {
            $this->save($tokens);
        }

        return $tokens;
    }

    private function configToken(string $key): ?string
    {
        $token = $this->config->get("vandar.tokens.{$key}");

        return is_string($token) && $token !== '' ? $token : null;
    }

    private function cacheKey(): string
    {
        $key = $this->config->get('vandar.tokens.cache_key', 'vandar.tokens');

        return is_string($key) && $key !== '' ? $key : 'vandar.tokens';
    }

    private function cacheTtlSeconds(): int
    {
        $refreshTokenTtl = $this->config->get('vandar.tokens.refresh_token_ttl_seconds');
        $accessTokenTtl = $this->config->get('vandar.tokens.access_token_ttl_seconds');
        $ttl = is_numeric($refreshTokenTtl) ? (int) $refreshTokenTtl : (is_numeric($accessTokenTtl) ? (int) $accessTokenTtl : 432000);

        return max(1, $ttl);
    }

    private function accessTokenTtlSeconds(): ?int
    {
        $ttl = $this->config->get('vandar.tokens.access_token_ttl_seconds');

        return is_numeric($ttl) ? (int) $ttl : null;
    }

    private function accessTokenExpiresAt(): mixed
    {
        return $this->config->get('vandar.tokens.access_token_expires_at');
    }

    private function shouldPersistConfigFallbackToCache(): bool
    {
        $value = $this->config->get('vandar.tokens.persist_config_fallback_to_cache', false);

        if (is_bool($value)) {
            return $value;
        }

        if (is_string($value)) {
            $parsed = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

            return is_bool($parsed) ? $parsed : false;
        }

        if (is_numeric($value)) {
            return (bool) (int) $value;
        }

        return false;
    }

    private function shouldEncryptCache(): bool
    {
        return (bool) $this->config->get('vandar.tokens.encrypt_cache', true);
    }
}
