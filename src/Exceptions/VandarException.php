<?php

declare(strict_types=1);

namespace Zarbinco\LaravelVandar\Exceptions;

use RuntimeException;
use Throwable;
use Zarbinco\LaravelVandar\Support\SensitiveDataRedactor;
use Zarbinco\LaravelVandar\Support\SensitiveUrlSanitizer;

class VandarException extends RuntimeException
{
    /**
     * @var array<string, mixed>
     */
    private readonly array $context;

    /**
     * @param  array<string, mixed>  $context
     */
    public function __construct(
        string $message = '',
        int $code = 0,
        ?Throwable $previous = null,
        array $context = [],
    ) {
        $this->context = self::sanitizeContext($context);

        parent::__construct($message, $code, $previous);
    }

    /**
     * @return array<string, mixed>
     */
    public function context(): array
    {
        return $this->context;
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    private static function sanitizeContext(array $context): array
    {
        return self::sanitizeUrlValues(SensitiveDataRedactor::redact($context));
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private static function sanitizeUrlValues(array $payload): array
    {
        $sanitized = [];

        foreach ($payload as $key => $value) {
            if (is_array($value)) {
                $sanitized[$key] = self::sanitizeUrlValues($value);

                continue;
            }

            if (is_string($key) && is_string($value) && self::isUrlKey($key)) {
                $sanitized[$key] = SensitiveUrlSanitizer::sanitize($value);

                continue;
            }

            if (is_string($key) && is_string($value) && self::isSensitiveMessageKey($key, $value)) {
                $sanitized[$key] = '[redacted]';

                continue;
            }

            $sanitized[$key] = $value;
        }

        return $sanitized;
    }

    private static function isUrlKey(string $key): bool
    {
        $normalizedKey = mb_strtolower($key);

        return in_array($normalizedKey, ['url', 'uri', 'endpoint'], true)
            || str_ends_with($normalizedKey, '_url')
            || str_ends_with($normalizedKey, '_uri');
    }

    private static function isSensitiveMessageKey(string $key, string $value): bool
    {
        if (! in_array(mb_strtolower($key), ['message', 'error', 'error_message'], true)) {
            return false;
        }

        return preg_match('/(access[_-]?token|refresh[_-]?token|refreshtoken|authorization|bearer|api[_-]?key|card[_-]?number|cardnumber|valid[_-]?card[_-]?number|iban|account|national[_-]?code|fida|birth[_-]?date|birthday|postal[_-]?code|mobile|phone|cid|refnumber|trackingcode|track[_-]?id|settlement[_-]?(id|track)|transaction[_-]?id|transid|batch[_-]?id|transfer[_-]?id|deposit[_-]?id|reference|payment[_-]?token|callback[_-]?token|amount|wage|fee|signature|image)/i', $value) === 1;
    }
}
