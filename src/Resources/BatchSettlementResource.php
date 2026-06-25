<?php

declare(strict_types=1);

namespace Zarbinco\LaravelVandar\Resources;

use Zarbinco\LaravelVandar\DTO\VandarResponse;
use Zarbinco\LaravelVandar\Http\VandarClient;
use Zarbinco\LaravelVandar\Support\BusinessResolver;
use Zarbinco\LaravelVandar\Support\VandarPath;

final class BatchSettlementResource
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
        return $this->client->post('batch', $this->path($business, 'batches-settlement'), $payload, allowRetry: false);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function store(array $payload, ?string $business = null): VandarResponse
    {
        return $this->create($payload, $business);
    }

    /**
     * @param  array<string, mixed>  $query
     */
    public function list(array $query = [], ?string $business = null): VandarResponse
    {
        return $this->client->get('batch', $this->path($business, 'batches'), $query);
    }

    /**
     * @param  array<string, mixed>  $query
     */
    public function all(array $query = [], ?string $business = null): VandarResponse
    {
        return $this->list($query, $business);
    }

    public function details(string|int $batchId, ?string $business = null): VandarResponse
    {
        return $this->client->request(
            'GET',
            'batch',
            VandarPath::join($this->path($business, 'batch-settlements'), VandarPath::segment($batchId)),
            [
                'query' => [],
                '_sensitive_path_segments' => [(string) $batchId],
            ],
        );
    }

    public function find(string|int $batchId, ?string $business = null): VandarResponse
    {
        return $this->details($batchId, $business);
    }

    public function show(string|int $batchId, ?string $business = null): VandarResponse
    {
        return $this->details($batchId, $business);
    }

    private function path(?string $business, string $path): string
    {
        return VandarPath::join('api/v2/business', $this->business->segment($business), $path);
    }
}
