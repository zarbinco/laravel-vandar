<?php

declare(strict_types=1);

namespace Zarbinco\LaravelVandar\Resources;

use Zarbinco\LaravelVandar\DTO\VandarResponse;
use Zarbinco\LaravelVandar\Http\VandarClient;
use Zarbinco\LaravelVandar\Support\BusinessResolver;
use Zarbinco\LaravelVandar\Support\VandarPath;

final class IbanResource
{
    public function __construct(
        private readonly VandarClient $client,
        private readonly BusinessResolver $business,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public function create(string|int $customer, array $payload, ?string $business = null): VandarResponse
    {
        return $this->client->post('api', $this->basePath($customer, $business), $payload);
    }

    /**
     * @param  array<string, mixed>  $query
     */
    public function list(string|int $customer, array $query = [], ?string $business = null): VandarResponse
    {
        return $this->client->get('api', $this->basePath($customer, $business), $query);
    }

    /**
     * @param  array<string, mixed>  $query
     */
    public function all(string|int $customer, array $query = [], ?string $business = null): VandarResponse
    {
        return $this->list($customer, $query, $business);
    }

    public function delete(string|int $customer, string|int $iban, ?string $business = null): VandarResponse
    {
        return $this->client->delete('api', $this->ibanPath($customer, $iban, $business));
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function inquiry(string|int $customer, string|int $iban, array $payload = [], ?string $business = null): VandarResponse
    {
        return $this->client->post('api', VandarPath::join($this->ibanPath($customer, $iban, $business), 'inquiry'), $payload);
    }

    public function setDefault(string|int $customer, string|int $iban, ?string $business = null): VandarResponse
    {
        return $this->client->post('api', VandarPath::join($this->ibanPath($customer, $iban, $business), 'set-default'));
    }

    private function basePath(string|int $customer, ?string $business = null): string
    {
        return VandarPath::join(
            'v3/business',
            $this->business->segment($business),
            'customers',
            VandarPath::segment($customer),
            'ibans',
        );
    }

    private function ibanPath(string|int $customer, string|int $iban, ?string $business = null): string
    {
        return VandarPath::join($this->basePath($customer, $business), VandarPath::segment($iban));
    }
}
