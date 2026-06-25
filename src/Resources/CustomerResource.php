<?php

declare(strict_types=1);

namespace Zarbinco\LaravelVandar\Resources;

use Zarbinco\LaravelVandar\DTO\VandarResponse;
use Zarbinco\LaravelVandar\Http\VandarClient;
use Zarbinco\LaravelVandar\Support\BusinessResolver;
use Zarbinco\LaravelVandar\Support\VandarPath;

final class CustomerResource
{
    public function __construct(
        private readonly VandarClient $client,
        private readonly BusinessResolver $business,
        private readonly CustomerFieldResource $fields,
    ) {}

    /**
     * @param  array<string, mixed>  $query
     */
    public function list(array $query = [], ?string $business = null): VandarResponse
    {
        return $this->client->get('api', $this->basePath($business), $query);
    }

    /**
     * @param  array<string, mixed>  $query
     */
    public function all(array $query = [], ?string $business = null): VandarResponse
    {
        return $this->list($query, $business);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function create(array $payload, ?string $business = null): VandarResponse
    {
        return $this->client->post('api', $this->basePath($business), $payload);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function createIndividual(array $payload, ?string $business = null): VandarResponse
    {
        return $this->create($this->withType($payload, 'INDIVIDUAL'), $business);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function createLegal(array $payload, ?string $business = null): VandarResponse
    {
        return $this->create($this->withType($payload, 'LEGAL'), $business);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function update(string|int $customer, array $payload, ?string $business = null): VandarResponse
    {
        return $this->client->put('api', $this->customerPath($customer, $business), $payload);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function updateIndividual(string|int $customer, array $payload, ?string $business = null): VandarResponse
    {
        return $this->update($customer, $this->withType($payload, 'INDIVIDUAL'), $business);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function updateLegal(string|int $customer, array $payload, ?string $business = null): VandarResponse
    {
        return $this->update($customer, $this->withType($payload, 'LEGAL'), $business);
    }

    public function delete(string|int $customer, ?string $business = null): VandarResponse
    {
        return $this->client->delete('api', $this->customerPath($customer, $business));
    }

    public function find(string|int $customer, ?string $business = null): VandarResponse
    {
        return $this->client->get('api', $this->customerPath($customer, $business));
    }

    public function show(string|int $customer, ?string $business = null): VandarResponse
    {
        return $this->find($customer, $business);
    }

    public function fields(): CustomerFieldResource
    {
        return $this->fields;
    }

    public function walletBalance(string|int $customer, ?string $business = null): VandarResponse
    {
        return $this->client->get('api', VandarPath::join($this->customerPath($customer, $business), 'wallet'));
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function walletDeposit(string|int $customer, array $payload, ?string $business = null): VandarResponse
    {
        return $this->client->post('api', VandarPath::join($this->customerPath($customer, $business), 'wallet/deposit'), $payload);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function walletWithdraw(string|int $customer, array $payload, ?string $business = null): VandarResponse
    {
        return $this->client->post('api', VandarPath::join($this->customerPath($customer, $business), 'wallet/withdraw'), $payload);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function transactions(string|int $customer, array $payload = [], ?string $business = null): VandarResponse
    {
        return $this->client->post('api', VandarPath::join($this->customerPath($customer, $business), 'transactions'), $payload);
    }

    private function basePath(?string $business = null): string
    {
        return VandarPath::join('v2/business', $this->business->segment($business), 'customers');
    }

    private function customerPath(string|int $customer, ?string $business = null): string
    {
        return VandarPath::join($this->basePath($business), VandarPath::segment($customer));
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function withType(array $payload, string $type): array
    {
        $payload['type'] ??= $type;

        return $payload;
    }
}
