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
        'accessToken',
        'refreshToken',
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
        'accountNumber',
        'account',
        'bank_account',
        'bankAccount',
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
        'customer_id',
        'customerId',
        'customer_identifier',
        'customerIdentifier',
        'customer_code',
        'customerCode',
        'refnumber',
        'factor_number',
        'factorNumber',
        'factor_no',
        'factorNo',
        'tracking_code',
        'trackingcode',
        'trackingCode',
        'transactionId',
        'transaction_id',
        'transid',
        'track_id',
        'trackId',
        'authorization_id',
        'authorizationId',
        'withdrawal_id',
        'withdrawalId',
        'refund_id',
        'refundId',
        'mandate_id',
        'mandateId',
        'subscription_id',
        'subscriptionId',
        'settlement_id',
        'settlementId',
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

    /**
     * @param  array<array-key, mixed>  $payload
     * @param  array<int, mixed>  $extraSensitiveKeys
     * @return array<array-key, mixed>
     */
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

        $redacted = is_string($redacted) ? $redacted : $body;

        return self::redactStandaloneSensitiveValues($redacted, self::TEXT_REDACTED);
    }

    /**
     * @param  array<array-key, mixed>  $payload
     * @param  array<string, bool>  $sensitiveKeys
     * @return array<array-key, mixed>
     */
    private static function redactArray(array $payload, array $sensitiveKeys): array
    {
        $redacted = [];

        foreach ($payload as $key => $value) {
            $normalizedKey = is_string($key) ? mb_strtolower($key) : $key;

            if (is_string($normalizedKey) && array_key_exists($normalizedKey, $sensitiveKeys)) {
                $redacted[$key] = self::REDACTED;

                continue;
            }

            $redacted[$key] = self::redactValue($value, $sensitiveKeys);
        }

        return $redacted;
    }

    /**
     * @param  array<string, bool>  $sensitiveKeys
     */
    private static function redactValue(mixed $value, array $sensitiveKeys): mixed
    {
        if (is_array($value)) {
            return self::redactArray($value, $sensitiveKeys);
        }

        if (is_string($value)) {
            return self::redactStandaloneSensitiveValues($value, self::TEXT_REDACTED);
        }

        if (is_int($value) && self::isStandaloneSensitiveNumericValue((string) $value)) {
            return self::REDACTED;
        }

        if ($value instanceof \stdClass) {
            return self::redactObject($value, $sensitiveKeys);
        }

        return $value;
    }

    /**
     * @param  array<string, bool>  $sensitiveKeys
     */
    private static function redactObject(\stdClass $value, array $sensitiveKeys): \stdClass
    {
        $redacted = clone $value;

        foreach (get_object_vars($redacted) as $key => $propertyValue) {
            $normalizedKey = mb_strtolower($key);

            if (array_key_exists($normalizedKey, $sensitiveKeys)) {
                $redacted->{$key} = self::REDACTED;

                continue;
            }

            $redacted->{$key} = self::redactValue($propertyValue, $sensitiveKeys);
        }

        return $redacted;
    }

    private static function redactStandaloneSensitiveValues(string $body, string $marker): string
    {
        $redacted = preg_replace(
            '/(?<![A-Z0-9])IR(?:[ -]?\d){24}(?![A-Z0-9])/iu',
            $marker,
            $body,
        );

        $redacted = is_string($redacted) ? $redacted : $body;

        $redacted = preg_replace_callback(
            '/(?<![A-Z0-9])\d(?:[ -]?\d){12,18}(?![A-Z0-9])/iu',
            static fn (array $matches): string => self::isValidCardPan($matches[0]) ? $marker : $matches[0],
            $redacted,
        );

        $redacted = is_string($redacted) ? $redacted : $body;

        $redacted = preg_replace(
            '/(?<![A-Z0-9+])(?:09\d{9}|\+989\d{9})(?![A-Z0-9])/iu',
            $marker,
            $redacted,
        );

        $redacted = is_string($redacted) ? $redacted : $body;

        $redacted = preg_replace_callback(
            '/(?<![A-Z0-9+])\d{10}(?![A-Z0-9])/iu',
            static fn (array $matches): string => self::isValidIranianNationalCode($matches[0]) ? $marker : $matches[0],
            $redacted,
        );

        return is_string($redacted) ? $redacted : $body;
    }

    private static function isStandaloneSensitiveNumericValue(string $value): bool
    {
        return self::isValidCardPan($value)
            || preg_match('/^(?:09\d{9}|\+989\d{9})$/', $value) === 1
            || self::isValidIranianNationalCode($value);
    }

    private static function isValidCardPan(string $value): bool
    {
        $digits = preg_replace('/[ -]/', '', $value);

        if (! is_string($digits) || preg_match('/^\d{13,19}$/', $digits) !== 1) {
            return false;
        }

        if (preg_match('/^(\d)\1+$/', $digits) === 1) {
            return false;
        }

        return self::passesLuhnChecksum($digits);
    }

    private static function isValidIranianNationalCode(string $value): bool
    {
        if (preg_match('/^\d{10}$/', $value) !== 1 || preg_match('/^(\d)\1+$/', $value) === 1) {
            return false;
        }

        $sum = 0;

        for ($index = 0; $index < 9; $index++) {
            $sum += ((int) $value[$index]) * (10 - $index);
        }

        $remainder = $sum % 11;
        $checksum = (int) $value[9];

        return $remainder < 2
            ? $checksum === $remainder
            : $checksum === 11 - $remainder;
    }

    private static function passesLuhnChecksum(string $digits): bool
    {
        $sum = 0;
        $double = false;

        for ($index = strlen($digits) - 1; $index >= 0; $index--) {
            $digit = (int) $digits[$index];

            if ($double) {
                $digit *= 2;

                if ($digit > 9) {
                    $digit -= 9;
                }
            }

            $sum += $digit;
            $double = ! $double;
        }

        return $sum % 10 === 0;
    }

    private static function bodySensitiveKeyPattern(): string
    {
        return implode('|', array_map(
            static fn (string $key): string => preg_quote($key, '/'),
            [
                'token',
                'access_token',
                'accessToken',
                'refresh_token',
                'refreshToken',
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
                'account_number',
                'accountNumber',
                'bank_account',
                'bankAccount',
                'customer_id',
                'customerId',
                'customer_identifier',
                'customerIdentifier',
                'customer_code',
                'customerCode',
                'factor_number',
                'factorNumber',
                'factor_no',
                'factorNo',
                'transaction_id',
                'transactionId',
                'transId',
                'track_id',
                'trackId',
                'authorization_id',
                'authorizationId',
                'withdrawal_id',
                'withdrawalId',
                'refund_id',
                'refundId',
                'mandate_id',
                'mandateId',
                'subscription_id',
                'subscriptionId',
                'settlement_id',
                'settlementId',
                'settlement_track_id',
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
