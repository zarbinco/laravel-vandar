<?php

declare(strict_types=1);

namespace Zarbinco\LaravelVandar\Resources;

use Zarbinco\LaravelVandar\DTO\VandarResponse;
use Zarbinco\LaravelVandar\Http\VandarClient;
use Zarbinco\LaravelVandar\Support\BusinessResolver;
use Zarbinco\LaravelVandar\Support\VandarPath;

final class CustomerFieldResource
{
    public function __construct(
        private readonly VandarClient $client,
        private readonly BusinessResolver $business,
    ) {}

    /**
     * @param  array<string, mixed>  $query
     */
    public function list(array $query = [], ?string $business = null): VandarResponse
    {
        return $this->client->get('api', $this->basePath($business), $query);
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
    public function update(string|int $field, array $payload, ?string $business = null): VandarResponse
    {
        return $this->client->put('api', $this->fieldPath($field, $business), $payload);
    }

    public function delete(string|int $field, ?string $business = null): VandarResponse
    {
        return $this->client->delete('api', $this->fieldPath($field, $business));
    }

    public function find(string|int $field, ?string $business = null): VandarResponse
    {
        return $this->client->get('api', $this->fieldPath($field, $business));
    }

    public function show(string|int $field, ?string $business = null): VandarResponse
    {
        return $this->find($field, $business);
    }

    private function basePath(?string $business = null): string
    {
        return VandarPath::join('v2/business', $this->business->segment($business), 'customers/fields');
    }

    private function fieldPath(string|int $field, ?string $business = null): string
    {
        return VandarPath::join($this->basePath($business), VandarPath::segment($field));
    }
}
