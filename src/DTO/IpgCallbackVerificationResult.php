<?php

declare(strict_types=1);

namespace Zarbinco\LaravelVandar\DTO;

final class IpgCallbackVerificationResult
{
    public function __construct(
        private readonly string $token,
        private readonly ?string $callbackStatus,
        private readonly VandarResponse $response,
    ) {}

    public function token(): string
    {
        return $this->token;
    }

    public function callbackStatus(): ?string
    {
        return $this->callbackStatus;
    }

    public function callbackHasOkStatus(): bool
    {
        return $this->callbackStatus === 'OK';
    }

    public function response(): VandarResponse
    {
        return $this->response;
    }

    public function successful(): bool
    {
        return $this->response->successful();
    }

    public function failed(): bool
    {
        return $this->response->failed();
    }

    public function verified(): bool
    {
        if (! $this->successful()) {
            return false;
        }

        foreach (['status', 'data.status', 'payment_status', 'data.payment_status', 'verified', 'data.verified'] as $key) {
            $value = $this->scalar($key);

            if ($value !== null && $this->isSuccessfulValue($value)) {
                return true;
            }
        }

        return false;
    }

    public function transactionId(): ?string
    {
        return $this->string([
            'transactionId',
            'transaction_id',
            'transId',
            'transid',
            'refnumber',
            'ref_number',
            'data.transactionId',
            'data.transaction_id',
            'data.transId',
            'data.transid',
            'data.refnumber',
            'data.ref_number',
        ]);
    }

    public function factorNumber(): ?string
    {
        return $this->string([
            'factorNumber',
            'factor_number',
            'factornumber',
            'factorNo',
            'factor_no',
            'data.factorNumber',
            'data.factor_number',
            'data.factornumber',
            'data.factorNo',
            'data.factor_no',
        ]);
    }

    public function amount(): int|string|null
    {
        $amount = $this->scalar(['amount', 'data.amount']);

        return is_int($amount) || is_string($amount) ? $amount : null;
    }

    public function cardHash(): ?string
    {
        return $this->string([
            'cardHash',
            'card_hash',
            'cardhash',
            'data.cardHash',
            'data.card_hash',
            'data.cardhash',
        ]);
    }

    public function cid(): ?string
    {
        return $this->string([
            'cid',
            'CID',
            'data.cid',
            'data.CID',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'token' => $this->token,
            'callback_status' => $this->callbackStatus,
            'callback_has_ok_status' => $this->callbackHasOkStatus(),
            'successful' => $this->successful(),
            'verified' => $this->verified(),
            'transaction_id' => $this->transactionId(),
            'factor_number' => $this->factorNumber(),
            'amount' => $this->amount(),
            'card_hash' => $this->cardHash(),
            'cid' => $this->cid(),
            'response' => $this->response->toArray(),
        ];
    }

    /**
     * @param  array<int, string>|string  $keys
     */
    private function scalar(array|string $keys): mixed
    {
        return $this->response->scalar($keys);
    }

    /**
     * @param  array<int, string>|string  $keys
     */
    private function string(array|string $keys): ?string
    {
        return $this->response->string($keys);
    }

    private function isSuccessfulValue(mixed $value): bool
    {
        if ($value === true || $value === 1) {
            return true;
        }

        if (! is_string($value)) {
            return false;
        }

        return in_array(mb_strtolower(trim($value)), [
            '1',
            'ok',
            'paid',
            'success',
            'successful',
            'true',
            'verified',
        ], true);
    }
}
