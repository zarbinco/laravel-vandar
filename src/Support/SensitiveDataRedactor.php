<?php

declare(strict_types=1);

namespace Zarbinco\LaravelVandar\Support;

final class SensitiveDataRedactor
{
    private const REDACTED = '[redacted]';

    private const DEFAULT_SENSITIVE_KEYS = [
        'access_token',
        'refresh_token',
        'accesstoken',
        'refreshtoken',
        'token',
        'authorization',
        'api_key',
        'apikey',
        'x-api-key',
        'api-key',
        'password',
        'secret',
        'card',
        'card_number',
        'iban',
        'account_number',
        'national_code',
        'individual_national_code',
        'legal_national_code',
        'fida_code',
        'birthday',
        'birth_date',
        'postal_code',
        'mobile',
        'phone',
        'signature',
        'image',
        'images',
    ];

    public static function redact(array $payload, array $extraSensitiveKeys = []): array
    {
        $sensitiveKeys = array_map(
            static fn (mixed $key): string => mb_strtolower((string) $key),
            array_merge(self::DEFAULT_SENSITIVE_KEYS, $extraSensitiveKeys),
        );

        return self::redactArray($payload, array_fill_keys($sensitiveKeys, true));
    }

    private static function redactArray(array $payload, array $sensitiveKeys): array
    {
        $redacted = [];

        foreach ($payload as $key => $value) {
            $normalizedKey = is_string($key) ? mb_strtolower($key) : $key;

            if (is_string($normalizedKey) && array_key_exists($normalizedKey, $sensitiveKeys)) {
                $redacted[$key] = self::REDACTED;

                continue;
            }

            $redacted[$key] = is_array($value) ? self::redactArray($value, $sensitiveKeys) : $value;
        }

        return $redacted;
    }
}
