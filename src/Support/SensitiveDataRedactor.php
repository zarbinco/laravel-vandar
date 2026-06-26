<?php

declare(strict_types=1);

namespace Zarbinco\LaravelVandar\Support;

final class SensitiveDataRedactor
{
    private const REDACTED = '[redacted]';

    private const TEXT_REDACTED = '[REDACTED]';

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
        'valid_card_number',
        'pan',
        'card',
        'card_number',
        'cardnumber',
        'iban',
        'sheba',
        'destination_iban',
        'source_iban',
        'account_number',
        'account',
        'bank_account',
        'national_code',
        'nationalCode',
        'individual_national_code',
        'legal_national_code',
        'fida_code',
        'birthday',
        'birth_date',
        'birthDate',
        'birthCertificateNumber',
        'identity_number',
        'identityNumber',
        'postal_code',
        'mobile',
        'mobile_number',
        'phone',
        'email',
        'cid',
        'refnumber',
        'tracking_code',
        'trackingcode',
        'trackingCode',
        'transactionId',
        'transaction_id',
        'transid',
        'track_id',
        'settlement_id',
        'settlement_track_id',
        'batchId',
        'batch_id',
        'queued_id',
        'queuedId',
        'transfer_id',
        'deposit_id',
        'suspicious_payment_id',
        'payment_identifier',
        'cash_in_code',
        'reference',
        'payment_token',
        'callback_token',
        'balance',
        'last_balance',
        'statement',
        'realtime_statement',
        'label',
        'amount',
        'wage',
        'fee',
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

    public static function redactText(string $body): string
    {
        $keys = self::bodySensitiveKeyPattern();

        $redacted = preg_replace_callback(
            '/((["\'])\s*(?:'.$keys.')\s*\2\s*:\s*)(["\'])(?:\\\\.|(?!\3).)*\3/iu',
            static fn (array $matches): string => $matches[1].$matches[3].self::TEXT_REDACTED.$matches[3],
            $body,
        );

        $redacted = is_string($redacted) ? $redacted : $body;

        $redacted = preg_replace_callback(
            '/((["\'])\s*(?:'.$keys.')\s*\2\s*:\s*)(?!["\'{\[])([^,\}\]\r\n]+)/iu',
            static fn (array $matches): string => $matches[1].self::TEXT_REDACTED,
            $redacted,
        );

        $redacted = is_string($redacted) ? $redacted : $body;

        $redacted = preg_replace(
            '/\b(authorization)\s*([=:])\s*([^\r\n&;,]+)/iu',
            '$1$2'.self::TEXT_REDACTED,
            $redacted,
        );

        $redacted = is_string($redacted) ? $redacted : $body;

        $redacted = preg_replace(
            '/\b('.$keys.')\s*([=:])\s*([^\s&;,]+)/iu',
            '$1$2'.self::TEXT_REDACTED,
            $redacted,
        );

        $redacted = is_string($redacted) ? $redacted : $body;

        $redacted = preg_replace(
            '/"(?:\\\\.|[^"\\\\])*(?:access[_-]?token|refresh[_-]?token|api[_-]?key|bearer)(?:\\\\.|[^"\\\\])*"(?!\s*:)/iu',
            '"'.self::TEXT_REDACTED.'"',
            $redacted,
        );

        $redacted = is_string($redacted) ? $redacted : $body;

        $redacted = preg_replace(
            "/'(?:\\\\.|[^'\\\\])*(?:access[_-]?token|refresh[_-]?token|api[_-]?key|bearer)(?:\\\\.|[^'\\\\])*'(?!\s*:)/iu",
            "'".self::TEXT_REDACTED."'",
            $redacted,
        );

        $redacted = is_string($redacted) ? $redacted : $body;

        $redacted = preg_replace(
            '/(?<!["\'])\b[^\s"\'<>&;,]*(?:access[_-]?token|refresh[_-]?token|api[_-]?key|bearer)[^\s"\'<>&;,]*/iu',
            self::TEXT_REDACTED,
            $redacted,
        );

        return is_string($redacted) ? $redacted : $body;
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

    private static function bodySensitiveKeyPattern(): string
    {
        return implode('|', array_map(
            static fn (string $key): string => preg_quote($key, '/'),
            [
                'token',
                'access_token',
                'refresh_token',
                'api_key',
                'apiKey',
                'apikey',
                'authorization',
                'Authorization',
                'national_code',
                'nationalCode',
                'individual_national_code',
                'legal_national_code',
                'birth_date',
                'birthDate',
                'birthCertificateNumber',
                'identity_number',
                'identityNumber',
                'card_number',
                'cardNumber',
                'cardnumber',
                'pan',
                'iban',
                'sheba',
                'cid',
                'mobile',
                'phone',
                'email',
            ],
        ));
    }
}
