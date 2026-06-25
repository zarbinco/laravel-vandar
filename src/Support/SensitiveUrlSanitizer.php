<?php

declare(strict_types=1);

namespace Zarbinco\LaravelVandar\Support;

final class SensitiveUrlSanitizer
{
    private const REDACTED = '[redacted]';

    private const REDACTED_URL = '[redacted-url]';

    private const DEFAULT_SENSITIVE_KEYS = [
        'access_token',
        'refresh_token',
        'token',
        'authorization',
        'api_key',
        'apikey',
        'password',
        'secret',
        'card_number',
        'iban',
        'national_code',
        'mobile',
        'phone',
        'refreshtoken',
    ];

    public static function sanitize(string $url, array $extraSensitiveKeys = []): string
    {
        $parts = parse_url($url);

        if ($parts === false) {
            return self::REDACTED_URL;
        }

        if (str_contains($url, '://') && ! isset($parts['host'])) {
            return self::REDACTED_URL;
        }

        if (isset($parts['host']) && (str_contains($parts['host'], '[') || str_contains($parts['host'], ']'))) {
            return self::REDACTED_URL;
        }

        $query = self::sanitizeQuery($parts['query'] ?? null, $extraSensitiveKeys);

        if (isset($parts['host'])) {
            return self::buildAbsoluteUrl($parts, $query);
        }

        return self::buildRelativeUrl($parts, $query);
    }

    /**
     * @param  array<string, mixed>  $parts
     */
    private static function buildAbsoluteUrl(array $parts, string $query): string
    {
        $url = '';

        if (isset($parts['scheme'])) {
            $url .= $parts['scheme'].'://';
        }

        $url .= $parts['host'];

        if (isset($parts['port'])) {
            $url .= ':'.$parts['port'];
        }

        $url .= $parts['path'] ?? '';

        if ($query !== '') {
            $url .= '?'.$query;
        }

        return $url;
    }

    /**
     * @param  array<string, mixed>  $parts
     */
    private static function buildRelativeUrl(array $parts, string $query): string
    {
        $url = $parts['path'] ?? '';

        if ($url === '' && isset($parts['query'])) {
            $url = '';
        }

        if ($query !== '') {
            $url .= '?'.$query;
        }

        return $url;
    }

    private static function sanitizeQuery(?string $query, array $extraSensitiveKeys): string
    {
        if ($query === null || $query === '') {
            return '';
        }

        parse_str($query, $parameters);

        if (! is_array($parameters)) {
            return '';
        }

        $sensitiveKeys = array_fill_keys(array_map(
            static fn (mixed $key): string => mb_strtolower((string) $key),
            array_merge(self::DEFAULT_SENSITIVE_KEYS, $extraSensitiveKeys),
        ), true);

        $sanitized = self::sanitizeParameters($parameters, $sensitiveKeys);

        return http_build_query($sanitized);
    }

    /**
     * @param  array<string, mixed>  $parameters
     * @param  array<string, bool>  $sensitiveKeys
     * @return array<string, mixed>
     */
    private static function sanitizeParameters(array $parameters, array $sensitiveKeys): array
    {
        $sanitized = [];

        foreach ($parameters as $key => $value) {
            $normalizedKey = mb_strtolower((string) $key);

            if (array_key_exists($normalizedKey, $sensitiveKeys)) {
                $sanitized[$key] = self::REDACTED;

                continue;
            }

            $sanitized[$key] = is_array($value) ? self::sanitizeParameters($value, $sensitiveKeys) : $value;
        }

        return $sanitized;
    }
}
