<?php

declare(strict_types=1);

namespace Zarbinco\LaravelVandar\Resources;

use Zarbinco\LaravelVandar\DTO\VandarResponse;
use Zarbinco\LaravelVandar\Http\VandarClient;
use Zarbinco\LaravelVandar\Support\BusinessResolver;
use Zarbinco\LaravelVandar\Support\VandarPath;

final class QueuedSettlementResource
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
        return $this->client->post('api', $this->basePath($business), $payload, allowRetry: false);
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
        return $this->client->get('api', $this->basePath($business), $query);
    }

    /**
     * @param  array<string, mixed>  $query
     */
    public function all(array $query = [], ?string $business = null): VandarResponse
    {
        return $this->list($query, $business);
    }

    public function find(string|int $id, ?string $business = null): VandarResponse
    {
        return $this->client->request(
            'GET',
            'api',
            VandarPath::join($this->basePath($business), VandarPath::segment($id)),
            [
                'query' => [],
                '_sensitive_path_segments' => [(string) $id],
            ],
        );
    }

    public function show(string|int $id, ?string $business = null): VandarResponse
    {
        return $this->find($id, $business);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function cancel(array $payload, ?string $business = null): VandarResponse
    {
        $options = ['json' => $payload];

        if (array_key_exists('id', $payload)) {
            $options['_extra_sensitive_keys'] = ['id'];
        }

        return $this->client->request(
            'POST',
            'api',
            VandarPath::join($this->basePath($business), 'cancel'),
            $options,
            auth: true,
            allowRetry: false,
        );
    }

    public function cancelById(string|int $id, ?string $business = null): VandarResponse
    {
        return $this->cancel(['id' => $id], $business);
    }

    private function basePath(?string $business = null): string
    {
        return VandarPath::join('v3/business', $this->business->segment($business), 'settlement/queued');
    }
}
