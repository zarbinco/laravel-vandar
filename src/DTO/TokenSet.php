<?php

declare(strict_types=1);

namespace Zarbinco\LaravelVandar\DTO;

use Carbon\CarbonImmutable;
use Zarbinco\LaravelVandar\Exceptions\VandarTokenException;
use Zarbinco\LaravelVandar\Support\SensitiveDataRedactor;

final readonly class TokenSet
{
    public function __construct(
        public ?string $tokenType,
        public string $accessToken,
        public string $refreshToken,
        public ?int $expiresIn = null,
        public ?CarbonImmutable $expiresAt = null,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function fromArray(array $payload): self
    {
        $accessToken = $payload['access_token'] ?? null;
        $refreshToken = $payload['refresh_token'] ?? null;

        if (! is_string($accessToken) || $accessToken === '' || ! is_string($refreshToken) || $refreshToken === '') {
            throw new VandarTokenException('Token response is missing required token values.');
        }

        $expiresIn = isset($payload['expires_in']) && is_numeric($payload['expires_in'])
            ? (int) $payload['expires_in']
            : null;

        $expiresAt = null;

        if (isset($payload['expires_at']) && is_string($payload['expires_at']) && $payload['expires_at'] !== '') {
            $expiresAt = CarbonImmutable::parse($payload['expires_at']);
        } elseif ($expiresIn !== null) {
            $expiresAt = CarbonImmutable::now()->addSeconds($expiresIn);
        }

        return new self(
            tokenType: is_string($payload['token_type'] ?? null) && $payload['token_type'] !== ''
                ? $payload['token_type']
                : 'Bearer',
            accessToken: $accessToken,
            refreshToken: $refreshToken,
            expiresIn: $expiresIn,
            expiresAt: $expiresAt,
        );
    }

    public static function fromConfig(?string $accessToken, ?string $refreshToken, ?int $expiresIn = null): ?self
    {
        if (! is_string($accessToken) || $accessToken === '' || ! is_string($refreshToken) || $refreshToken === '') {
            return null;
        }

        return new self(
            tokenType: 'Bearer',
            accessToken: $accessToken,
            refreshToken: $refreshToken,
            expiresIn: $expiresIn,
            expiresAt: $expiresIn === null ? null : CarbonImmutable::now()->addSeconds($expiresIn),
        );
    }

    public function authorizationHeader(): string
    {
        return sprintf('%s %s', $this->tokenType ?: 'Bearer', $this->accessToken);
    }

    public function isExpired(?CarbonImmutable $now = null): bool
    {
        if ($this->expiresAt === null) {
            return false;
        }

        return ($now ?? CarbonImmutable::now())->greaterThanOrEqualTo($this->expiresAt);
    }

    public function shouldRefresh(?CarbonImmutable $now = null, int $refreshBeforeSeconds = 3600): bool
    {
        if ($this->expiresAt === null) {
            return false;
        }

        return ($now ?? CarbonImmutable::now())
            ->addSeconds($refreshBeforeSeconds)
            ->greaterThanOrEqualTo($this->expiresAt);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'token_type' => $this->tokenType,
            'access_token' => $this->accessToken,
            'refresh_token' => $this->refreshToken,
            'expires_in' => $this->expiresIn,
            'expires_at' => $this->expiresAt?->toIso8601String(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function redactedArray(): array
    {
        return SensitiveDataRedactor::redact($this->toArray());
    }
}
