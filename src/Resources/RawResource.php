<?php

declare(strict_types=1);

namespace Zarbinco\LaravelVandar\Resources;

use Zarbinco\LaravelVandar\DTO\VandarResponse;
use Zarbinco\LaravelVandar\Http\VandarClient;

final class RawResource
{
    public function __construct(
        private readonly VandarClient $client,
    ) {}

    /**
     * @param  array<string, mixed>  $query
     */
    public function get(string $base, string $path, array $query = [], bool $auth = true): VandarResponse
    {
        return $this->client->get($base, $path, $query, $auth);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function post(string $base, string $path, array $payload = [], bool $auth = true): VandarResponse
    {
        return $this->client->post($base, $path, $payload, $auth);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function put(string $base, string $path, array $payload = [], bool $auth = true): VandarResponse
    {
        return $this->client->put($base, $path, $payload, $auth);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function patch(string $base, string $path, array $payload = [], bool $auth = true): VandarResponse
    {
        return $this->client->patch($base, $path, $payload, $auth);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function delete(string $base, string $path, array $payload = [], bool $auth = true): VandarResponse
    {
        return $this->client->delete($base, $path, $payload, $auth);
    }
}
