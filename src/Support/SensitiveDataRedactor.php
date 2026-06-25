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
        'valid_card_number',
        'card',
        'card_number',
        'cardnumber',
        'iban',
        'destination_iban',
        'source_iban',
        'account_number',
        'account',
        'bank_account',
        'national_code',
        'individual_national_code',
        'legal_national_code',
        'fida_code',
        'birthday',
        'birth_date',
        'postal_code',
        'mobile',
        'mobile_number',
        'phone',
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
