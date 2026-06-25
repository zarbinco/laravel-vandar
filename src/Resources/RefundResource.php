<?php

declare(strict_types=1);

namespace Zarbinco\LaravelVandar\Resources;

use Zarbinco\LaravelVandar\DTO\VandarResponse;
use Zarbinco\LaravelVandar\Http\VandarClient;
use Zarbinco\LaravelVandar\Support\BusinessResolver;
use Zarbinco\LaravelVandar\Support\VandarPath;

final class RefundResource
{
    public function __construct(
        private readonly VandarClient $client,
        private readonly BusinessResolver $business,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public function create(string|int $transactionId, array $payload = [], ?string $business = null): VandarResponse
    {
        return $this->client->request(
            'POST',
            'api',
            VandarPath::join(
                'v3/business',
                $this->business->segment($business),
                'transaction',
                VandarPath::segment($transactionId),
                'refund',
            ),
            [
                'json' => $payload,
                '_sensitive_path_segments' => [(string) $transactionId],
            ],
            auth: true,
            allowRetry: false,
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function transaction(string|int $transactionId, array $payload = [], ?string $business = null): VandarResponse
    {
        return $this->create($transactionId, $payload, $business);
    }
}
