<?php

declare(strict_types=1);

namespace Zarbinco\LaravelVandar\Token;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Zarbinco\LaravelVandar\Contracts\TokenStore;
use Zarbinco\LaravelVandar\DTO\TokenSet;
use Zarbinco\LaravelVandar\Exceptions\VandarUnsupportedTokenStoreException;

final class ConfigTokenStore implements TokenStore
{
    public function __construct(
        private readonly ConfigRepository $config,
    ) {}

    public function accessToken(): ?string
    {
        $token = $this->config->get('vandar.tokens.access_token');

        return is_string($token) && $token !== '' ? $token : null;
    }

    public function refreshToken(): ?string
    {
        $token = $this->config->get('vandar.tokens.refresh_token');

        return is_string($token) && $token !== '' ? $token : null;
    }

    public function tokens(): ?TokenSet
    {
        return TokenSet::fromConfig(
            $this->accessToken(),
            $this->refreshToken(),
            $this->accessTokenTtlSeconds(),
        );
    }

    public function save(TokenSet $tokens): void
    {
        throw new VandarUnsupportedTokenStoreException('The config token store is read-only at runtime.');
    }

    public function clear(): void
    {
        //
    }

    private function accessTokenTtlSeconds(): ?int
    {
        $ttl = $this->config->get('vandar.tokens.access_token_ttl_seconds');

        return is_numeric($ttl) ? (int) $ttl : null;
    }
}
