<?php

declare(strict_types=1);

namespace Zarbinco\LaravelVandar\Resources;

use Zarbinco\LaravelVandar\DTO\VandarResponse;
use Zarbinco\LaravelVandar\Http\VandarClient;
use Zarbinco\LaravelVandar\Support\BusinessResolver;
use Zarbinco\LaravelVandar\Support\VandarPath;

final class AvandResource
{
    public function __construct(
        private readonly VandarClient $client,
        private readonly BusinessResolver $business,
    ) {}

    public function account(?string $business = null): VandarResponse
    {
        return $this->client->get('api', $this->cashInAccountPath($business));
    }

    public function cashInAccount(?string $business = null): VandarResponse
    {
        return $this->account($business);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function deposit(array $payload, ?string $business = null): VandarResponse
    {
        return $this->client->post('api', VandarPath::join($this->cashInAccountPath($business), 'deposit'), $payload, allowRetry: false);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function balance(array $payload = [], ?string $business = null): VandarResponse
    {
        return $this->client->post('api', VandarPath::join($this->cashInAccountPath($business), 'balance'), $payload);
    }

    public function lastBalance(string|int $iban, ?string $business = null): VandarResponse
    {
        return $this->client->request(
            'GET',
            'api',
            VandarPath::join($this->settlementAccountPath($business), VandarPath::segment($iban), 'last-balance'),
            [
                'query' => [],
                '_sensitive_path_segments' => [(string) $iban],
            ],
        );
    }

    public function accountLastBalance(string|int $iban, ?string $business = null): VandarResponse
    {
        return $this->lastBalance($iban, $business);
    }

    /**
     * @param  array<string, mixed>  $query
     */
    public function statement(array $query = [], ?string $business = null): VandarResponse
    {
        return $this->client->get('api', VandarPath::join($this->settlementAccountPath($business), 'statement'), $query);
    }

    /**
     * @param  array<string, mixed>  $query
     */
    public function realtimeStatement(array $query = [], ?string $business = null): VandarResponse
    {
        return $this->client->get('api', VandarPath::join($this->settlementAccountPath($business), 'realtime-statement'), $query);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function addTransactionLabel(string|int $iban, string|int $trackingCode, array $payload = [], ?string $business = null): VandarResponse
    {
        return $this->client->request(
            'POST',
            'api',
            $this->transactionLabelPath($iban, $trackingCode, $business),
            [
                'json' => $payload,
                '_sensitive_path_segments' => [(string) $iban, (string) $trackingCode],
            ],
            auth: true,
            allowRetry: false,
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function removeTransactionLabel(string|int $iban, string|int $trackingCode, array $payload = [], ?string $business = null): VandarResponse
    {
        return $this->client->request(
            'DELETE',
            'api',
            $this->transactionLabelPath($iban, $trackingCode, $business),
            [
                'json' => $payload,
                '_sensitive_path_segments' => [(string) $iban, (string) $trackingCode],
            ],
            auth: true,
            allowRetry: false,
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function deleteTransactionLabel(string|int $iban, string|int $trackingCode, array $payload = [], ?string $business = null): VandarResponse
    {
        return $this->removeTransactionLabel($iban, $trackingCode, $payload, $business);
    }

    public function code(?string $business = null): VandarResponse
    {
        return $this->client->get('api', $this->cashInPath($business, 'code'));
    }

    public function cashInCode(?string $business = null): VandarResponse
    {
        return $this->code($business);
    }

    /**
     * @param  array<string, mixed>  $query
     */
    public function picTransactions(array $query = [], ?string $business = null): VandarResponse
    {
        return $this->client->get('api', $this->cashInPath($business, 'pic/transactions'), $query);
    }

    /**
     * @param  array<string, mixed>  $query
     */
    public function suspiciousPayments(array $query = [], ?string $business = null): VandarResponse
    {
        return $this->client->get('api', $this->cashInPath($business, 'suspicious-payment'), $query);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function suspiciousPayment(string|int $id, array $payload = [], ?string $business = null): VandarResponse
    {
        return $this->client->request(
            'POST',
            'api',
            VandarPath::join($this->cashInPath($business, 'suspicious-payment'), VandarPath::segment($id)),
            [
                'json' => $payload,
                '_sensitive_path_segments' => [(string) $id],
            ],
            auth: true,
            allowRetry: false,
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function resolveSuspiciousPayment(string|int $id, array $payload = [], ?string $business = null): VandarResponse
    {
        return $this->suspiciousPayment($id, $payload, $business);
    }

    private function cashInAccountPath(?string $business = null): string
    {
        return VandarPath::join('v3/business', $this->business->segment($business), 'cash-in/account');
    }

    private function settlementAccountPath(?string $business = null): string
    {
        return VandarPath::join('v3/business', $this->business->segment($business), 'settlement/account');
    }

    private function cashInPath(?string $business, string $path): string
    {
        return VandarPath::join('v3/business', $this->business->segment($business), 'cash-in', $path);
    }

    private function transactionLabelPath(string|int $iban, string|int $trackingCode, ?string $business = null): string
    {
        return VandarPath::join(
            $this->settlementAccountPath($business),
            VandarPath::segment($iban),
            'transaction',
            VandarPath::segment($trackingCode),
            'label',
        );
    }
}
