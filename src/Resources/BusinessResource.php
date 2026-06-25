<?php

declare(strict_types=1);

namespace Zarbinco\LaravelVandar\Resources;

use Zarbinco\LaravelVandar\DTO\VandarResponse;
use Zarbinco\LaravelVandar\Http\VandarClient;
use Zarbinco\LaravelVandar\Support\BusinessResolver;
use Zarbinco\LaravelVandar\Support\VandarPath;

final class BusinessResource
{
    public function __construct(
        private readonly VandarClient $client,
        private readonly BusinessResolver $business,
    ) {}

    public function info(?string $business = null): VandarResponse
    {
        return $this->client->get('api', $this->path('v2/business', $business));
    }

    public function users(?string $business = null): VandarResponse
    {
        return $this->client->get('api', $this->path('v2/business', $business, 'iam'));
    }

    public function balance(?string $business = null): VandarResponse
    {
        return $this->client->get('api', $this->path('v2/business', $business, 'balance'));
    }

    public function wallet(?string $business = null): VandarResponse
    {
        return $this->balance($business);
    }

    /**
     * @param  array<string, mixed>  $query
     */
    public function transactions(array $query = [], ?string $business = null): VandarResponse
    {
        return $this->client->get('api', $this->path('v3/business', $business, 'transaction'), $query);
    }

    private function path(string $prefix, ?string $business = null, string ...$suffix): string
    {
        return VandarPath::join($prefix, $this->business->segment($business), ...$suffix);
    }
}
