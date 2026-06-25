<?php

declare(strict_types=1);

namespace Zarbinco\LaravelVandar\Contracts;

use Zarbinco\LaravelVandar\DTO\TokenSet;

interface TokenStore
{
    public function accessToken(): ?string;

    public function refreshToken(): ?string;

    public function save(TokenSet $tokens): void;

    public function clear(): void;
}
