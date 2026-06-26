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
        'valid_card_number',
        'card',
        'card_number',
        'cardnumber',
        'iban',
        'destination_iban',
        'source_iban',
        'account_number',
        'accountnumber',
        'account',
        'bank_account',
        'bankaccount',
        'national_code',
        'nationalcode',
        'individual_national_code',
        'legal_national_code',
        'fida_code',
        'birthday',
        'birth_date',
        'birthdate',
        'birthcertificatenumber',
        'identity_number',
        'identitynumber',
        'postal_code',
        'mobile',
        'mobile_number',
        'phone',
        'cid',
        'refnumber',
        'tracking_code',
        'trackingcode',
        'transaction_id',
        'transid',
        'track_id',
        'trackid',
        'authorization_id',
        'authorizationid',
        'withdrawal_id',
        'withdrawalid',
        'refund_id',
        'refundid',
        'mandate_id',
        'mandateid',
        'subscription_id',
        'subscriptionid',
        'settlement_id',
        'settlement_track_id',
        'batch_id',
        'batchid',
        'queued_id',
        'queuedid',
        'transfer_id',
        'deposit_id',
        'suspicious_payment_id',
        'payment_identifier',
        'cash_in_code',
        'reference',
        'payment_token',
        'callback_token',
        'balance',
        'amount',
        'wage',
        'fee',
        'signature',
        'image',
        'images',
        'refreshtoken',
    ];

    /**
     * @param  array<int, mixed>  $extraSensitiveKeys
     * @param  array<int, mixed>  $sensitivePathSegments
     */
    public static function sanitize(string $url, array $extraSensitiveKeys = [], array $sensitivePathSegments = []): string
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

        $parts['path'] = self::sanitizePath($parts['path'] ?? '', $sensitivePathSegments);
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

    /**
     * @param  array<int, mixed>  $extraSensitiveKeys
     */
    private static function sanitizeQuery(?string $query, array $extraSensitiveKeys): string
    {
        if ($query === null || $query === '') {
            return '';
        }

        parse_str($query, $parameters);

        $sensitiveKeys = array_fill_keys(array_map(
            static fn (mixed $key): string => mb_strtolower((string) $key),
            array_merge(self::DEFAULT_SENSITIVE_KEYS, $extraSensitiveKeys),
        ), true);

        $sanitized = self::sanitizeParameters($parameters, $sensitiveKeys);

        return http_build_query($sanitized);
    }

    /**
     * @param  array<int, mixed>  $sensitivePathSegments
     */
    private static function sanitizePath(string $path, array $sensitivePathSegments): string
    {
        if ($path === '' || $sensitivePathSegments === []) {
            return $path;
        }

        $sensitiveSegments = array_fill_keys(array_values(array_filter(
            array_map(static fn (mixed $segment): string => (string) $segment, $sensitivePathSegments),
            static fn (string $segment): bool => $segment !== '',
        )), true);

        if ($sensitiveSegments === []) {
            return $path;
        }

        $segments = explode('/', $path);

        foreach ($segments as $index => $segment) {
            if ($segment === '') {
                continue;
            }

            if (array_key_exists(rawurldecode($segment), $sensitiveSegments)) {
                $segments[$index] = self::REDACTED;
            }
        }

        return implode('/', $segments);
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
