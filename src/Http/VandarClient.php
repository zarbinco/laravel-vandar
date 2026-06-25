<?php

declare(strict_types=1);

namespace Zarbinco\LaravelVandar\Http;

use Zarbinco\LaravelVandar\DTO\VandarResponse;

final class VandarClient
{
    public function __construct(
        private readonly PendingVandarRequest $pendingRequest,
    ) {}

    /**
     * @param  array<string, mixed>  $query
     */
    public function get(string $base, string $path, array $query = [], bool $auth = true, bool $allowRetry = true): VandarResponse
    {
        return $this->request('GET', $base, $path, ['query' => $query], $auth, $allowRetry);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function post(string $base, string $path, array $payload = [], bool $auth = true, bool $allowRetry = true): VandarResponse
    {
        return $this->request('POST', $base, $path, ['json' => $payload], $auth, $allowRetry);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function put(string $base, string $path, array $payload = [], bool $auth = true, bool $allowRetry = true): VandarResponse
    {
        return $this->request('PUT', $base, $path, ['json' => $payload], $auth, $allowRetry);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function patch(string $base, string $path, array $payload = [], bool $auth = true, bool $allowRetry = true): VandarResponse
    {
        return $this->request('PATCH', $base, $path, ['json' => $payload], $auth, $allowRetry);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function delete(string $base, string $path, array $payload = [], bool $auth = true, bool $allowRetry = true): VandarResponse
    {
        return $this->request('DELETE', $base, $path, ['json' => $payload], $auth, $allowRetry);
    }

    /**
     * @param  array<string, mixed>  $options
     */
    public function request(string $method, string $base, string $path, array $options = [], bool $auth = true, bool $allowRetry = true): VandarResponse
    {
        return $this->pendingRequest->send($method, $base, $path, $options, $auth, $allowRetry);
    }
}
