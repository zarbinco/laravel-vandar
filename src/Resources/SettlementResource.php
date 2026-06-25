<?php

declare(strict_types=1);

namespace Zarbinco\LaravelVandar\Resources;

use Zarbinco\LaravelVandar\DTO\VandarResponse;
use Zarbinco\LaravelVandar\Http\VandarClient;
use Zarbinco\LaravelVandar\Support\BusinessResolver;
use Zarbinco\LaravelVandar\Support\VandarPath;

final class SettlementResource
{
    public function __construct(
        private readonly VandarClient $client,
        private readonly BusinessResolver $business,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public function create(array $payload, ?string $business = null): VandarResponse
    {
        return $this->client->post('api', $this->v3Path($business, 'settlement/store'), $payload, allowRetry: false);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function store(array $payload, ?string $business = null): VandarResponse
    {
        return $this->create($payload, $business);
    }

    public function find(string|int $trackId, ?string $business = null): VandarResponse
    {
        return $this->client->request(
            'GET',
            'api',
            VandarPath::join($this->v4Path($business, 'settlement'), VandarPath::segment($trackId)),
            [
                'query' => [],
                '_sensitive_path_segments' => [(string) $trackId],
            ],
        );
    }

    public function show(string|int $trackId, ?string $business = null): VandarResponse
    {
        return $this->find($trackId, $business);
    }

    /**
     * @param  array<string, mixed>  $query
     */
    public function list(array $query = [], ?string $business = null): VandarResponse
    {
        return $this->client->get('api', $this->v4Path($business, 'settlement'), $query);
    }

    /**
     * @param  array<string, mixed>  $query
     */
    public function all(array $query = [], ?string $business = null): VandarResponse
    {
        return $this->list($query, $business);
    }

    public function cancel(string|int $trackId, ?string $business = null): VandarResponse
    {
        return $this->client->request(
            'DELETE',
            'api',
            VandarPath::join($this->v4Path($business, 'settlement'), VandarPath::segment($trackId)),
            [
                'json' => [],
                '_sensitive_path_segments' => [(string) $trackId],
            ],
            auth: true,
            allowRetry: false,
        );
    }

    /**
     * @param  array<string, mixed>  $query
     */
    public function banks(array $query = [], ?string $business = null): VandarResponse
    {
        return $this->client->get('api', $this->v3Path($business, 'settlement/banks'), $query);
    }

    private function v3Path(?string $business, string $path): string
    {
        return VandarPath::join('v3/business', $this->business->segment($business), $path);
    }

    private function v4Path(?string $business, string $path): string
    {
        return VandarPath::join('v4/business', $this->business->segment($business), $path);
    }
}
