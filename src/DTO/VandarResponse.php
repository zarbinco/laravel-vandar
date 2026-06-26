<?php

declare(strict_types=1);

namespace Zarbinco\LaravelVandar\DTO;

use Illuminate\Support\Arr;
use Zarbinco\LaravelVandar\Exceptions\VandarAuthenticationException;
use Zarbinco\LaravelVandar\Exceptions\VandarAuthorizationException;
use Zarbinco\LaravelVandar\Exceptions\VandarRateLimitException;
use Zarbinco\LaravelVandar\Exceptions\VandarRequestException;
use Zarbinco\LaravelVandar\Exceptions\VandarServerException;
use Zarbinco\LaravelVandar\Exceptions\VandarValidationException;
use Zarbinco\LaravelVandar\Support\SensitiveDataRedactor;

final class VandarResponse
{
    private const REDACTED_BODY_LIMIT = 4000;

    /**
     * @param  array<string, mixed>  $json
     * @param  array<string, mixed>  $headers
     */
    public function __construct(
        private readonly int $status,
        private readonly array $json = [],
        private readonly array $headers = [],
        private readonly ?string $body = null,
        private readonly bool $jsonParseFailed = false,
    ) {}

    public function status(): int
    {
        return $this->status;
    }

    public function json(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $this->json;
        }

        return Arr::get($this->json, $key, $default);
    }

    public function data(?string $key = null, mixed $default = null): mixed
    {
        $data = $this->json['data'] ?? $default;

        if ($key === null) {
            return $data;
        }

        return is_array($data) ? Arr::get($data, $key, $default) : $default;
    }

    public function body(): ?string
    {
        return $this->body;
    }

    public function hasBody(): bool
    {
        return is_string($this->body) && trim($this->body) !== '';
    }

    public function jsonParseFailed(): bool
    {
        return $this->jsonParseFailed;
    }

    public function contentType(): ?string
    {
        $contentType = $this->header('Content-Type');

        if (is_array($contentType)) {
            foreach ($contentType as $value) {
                if (is_scalar($value) && trim((string) $value) !== '') {
                    return (string) $value;
                }
            }

            return null;
        }

        if (is_scalar($contentType) && trim((string) $contentType) !== '') {
            return (string) $contentType;
        }

        return null;
    }

    public function isJson(): bool
    {
        $contentType = $this->contentType();

        return is_string($contentType) && str_contains(mb_strtolower($contentType), 'json');
    }

    public function redactedBody(): ?string
    {
        if ($this->body === null) {
            return null;
        }

        return $this->truncateBody(SensitiveDataRedactor::redactText($this->body));
    }

    /**
     * @param  array<int, string>|string  $keys
     */
    public function value(array|string $keys, mixed $default = null): mixed
    {
        foreach ($this->keyList($keys) as $key) {
            if (Arr::has($this->json, $key)) {
                return Arr::get($this->json, $key);
            }
        }

        return $default;
    }

    /**
     * @param  array<int, string>|string  $keys
     */
    public function scalar(array|string $keys, mixed $default = null): mixed
    {
        foreach ($this->keyList($keys) as $key) {
            if (! Arr::has($this->json, $key)) {
                continue;
            }

            $value = Arr::get($this->json, $key);

            if (is_scalar($value)) {
                return $value;
            }
        }

        return $default;
    }

    /**
     * @param  array<int, string>|string  $keys
     */
    public function string(array|string $keys, ?string $default = null): ?string
    {
        $value = $this->scalar($keys, $default);

        return is_scalar($value) ? (string) $value : $default;
    }

    /**
     * @param  array<int, string>|string  $keys
     */
    public function int(array|string $keys, ?int $default = null): ?int
    {
        foreach ($this->keyList($keys) as $key) {
            if (! Arr::has($this->json, $key)) {
                continue;
            }

            $value = Arr::get($this->json, $key);

            if (is_int($value)) {
                return $value;
            }

            if ((is_float($value) || is_string($value)) && is_numeric($value)) {
                return (int) $value;
            }
        }

        return $default;
    }

    /**
     * @param  array<int, string>|string  $keys
     */
    public function bool(array|string $keys, ?bool $default = null): ?bool
    {
        foreach ($this->keyList($keys) as $key) {
            if (! Arr::has($this->json, $key)) {
                continue;
            }

            $value = Arr::get($this->json, $key);

            if (is_bool($value)) {
                return $value;
            }

            if (is_int($value) && in_array($value, [0, 1], true)) {
                return $value === 1;
            }

            if (is_string($value)) {
                $boolean = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

                if (is_bool($boolean)) {
                    return $boolean;
                }
            }
        }

        return $default;
    }

    public function message(?string $default = null): ?string
    {
        foreach (['message', 'error', 'error_message'] as $key) {
            $message = $this->json[$key] ?? null;

            if (is_string($message) && $message !== '') {
                return $message;
            }
        }

        return $default;
    }

    /**
     * @return array<string, mixed>
     */
    public function errors(): array
    {
        if (isset($this->json['errors']) && is_array($this->json['errors'])) {
            return $this->json['errors'];
        }

        if (array_key_exists('error', $this->json)) {
            return is_array($this->json['error'])
                ? $this->json['error']
                : ['error' => $this->json['error']];
        }

        return [];
    }

    public function trackId(): ?string
    {
        foreach (['track_id', 'trackId', 'tracking_code'] as $key) {
            $trackId = $this->json[$key] ?? null;

            if (is_scalar($trackId) && $trackId !== '') {
                return (string) $trackId;
            }
        }

        return null;
    }

    public function successful(): bool
    {
        return $this->status >= 200 && $this->status <= 299;
    }

    public function failed(): bool
    {
        return ! $this->successful();
    }

    public function clientError(): bool
    {
        return $this->status >= 400 && $this->status <= 499;
    }

    public function serverError(): bool
    {
        return $this->status >= 500 && $this->status <= 599;
    }

    public function unauthorized(): bool
    {
        return $this->status === 401;
    }

    public function forbidden(): bool
    {
        return $this->status === 403;
    }

    public function notFound(): bool
    {
        return $this->status === 404;
    }

    public function tooManyRequests(): bool
    {
        return $this->status === 429;
    }

    /**
     * @return array<string, mixed>
     */
    public function headers(): array
    {
        return $this->headers;
    }

    public function header(string $name, mixed $default = null): mixed
    {
        foreach ($this->headers as $key => $value) {
            if (strtolower((string) $key) === strtolower($name)) {
                return is_array($value) && count($value) === 1 ? $value[0] : $value;
            }
        }

        return $default;
    }

    public function throw(): self
    {
        if ($this->successful()) {
            return $this;
        }

        $message = $this->message('Vandar request failed.');
        $response = SensitiveDataRedactor::redact($this->toArray());

        throw match (true) {
            $this->status === 401 => new VandarAuthenticationException($message, $this->status, $response),
            $this->status === 403 => new VandarAuthorizationException($message, $this->status, $response),
            $this->status === 422 => new VandarValidationException($message, $this->status, $response),
            $this->status === 429 => new VandarRateLimitException($message, $this->status, $response),
            $this->serverError() => new VandarServerException($message, $this->status, $response),
            default => new VandarRequestException($message, $this->status, $response),
        };
    }

    /**
     * @return array{status: int, json: array<string, mixed>, headers: array<string, mixed>, redacted_body: ?string, json_parse_failed: bool, content_type: ?string}
     */
    public function toArray(): array
    {
        return [
            'status' => $this->status,
            'json' => $this->json,
            'headers' => $this->headers,
            'redacted_body' => $this->redactedBody(),
            'json_parse_failed' => $this->jsonParseFailed(),
            'content_type' => $this->contentType(),
        ];
    }

    /**
     * @param  array<int, string>|string  $keys
     * @return array<int, string>
     */
    private function keyList(array|string $keys): array
    {
        return is_array($keys) ? array_values($keys) : [$keys];
    }

    private function truncateBody(string $body): string
    {
        if (mb_strlen($body) <= self::REDACTED_BODY_LIMIT) {
            return $body;
        }

        return mb_substr($body, 0, self::REDACTED_BODY_LIMIT);
    }
}
