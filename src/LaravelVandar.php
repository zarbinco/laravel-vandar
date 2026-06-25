<?php

declare(strict_types=1);

namespace Zarbinco\LaravelVandar;

use Composer\InstalledVersions;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Zarbinco\LaravelVandar\DTO\VandarResponse;
use Zarbinco\LaravelVandar\Exceptions\VandarException;
use Zarbinco\LaravelVandar\Http\VandarClient;
use Zarbinco\LaravelVandar\Resources\AvandResource;
use Zarbinco\LaravelVandar\Resources\BatchSettlementResource;
use Zarbinco\LaravelVandar\Resources\BusinessResource;
use Zarbinco\LaravelVandar\Resources\CardResource;
use Zarbinco\LaravelVandar\Resources\CustomerResource;
use Zarbinco\LaravelVandar\Resources\IbanResource;
use Zarbinco\LaravelVandar\Resources\InquiryResource;
use Zarbinco\LaravelVandar\Resources\IpgResource;
use Zarbinco\LaravelVandar\Resources\QueuedSettlementResource;
use Zarbinco\LaravelVandar\Resources\RawResource;
use Zarbinco\LaravelVandar\Resources\RefundResource;
use Zarbinco\LaravelVandar\Resources\SettlementResource;
use Zarbinco\LaravelVandar\Token\TokenManager;

final class LaravelVandar
{
    public function __construct(
        private readonly ConfigRepository $configRepository,
        private readonly VandarClient $client,
        private readonly TokenManager $tokens,
        private readonly RawResource $raw,
        private readonly BusinessResource $business,
        private readonly CustomerResource $customers,
        private readonly CardResource $cards,
        private readonly IbanResource $ibans,
        private readonly InquiryResource $inquiries,
        private readonly IpgResource $ipg,
        private readonly RefundResource $refunds,
        private readonly SettlementResource $settlements,
        private readonly QueuedSettlementResource $queuedSettlements,
        private readonly BatchSettlementResource $batchSettlements,
        private readonly AvandResource $avand,
    ) {}

    public function name(): string
    {
        return 'Laravel Vandar SDK';
    }

    public function version(): string
    {
        if (! class_exists(InstalledVersions::class)) {
            return 'dev';
        }

        if (
            InstalledVersions::isInstalled('zarbinco/laravel-vandar')
            && InstalledVersions::getPrettyVersion('zarbinco/laravel-vandar') !== null
        ) {
            return InstalledVersions::getPrettyVersion('zarbinco/laravel-vandar');
        }

        if (method_exists(InstalledVersions::class, 'getRootPackage')) {
            $rootPackage = InstalledVersions::getRootPackage();

            if (($rootPackage['name'] ?? null) === 'zarbinco/laravel-vandar') {
                return $rootPackage['pretty_version'] ?? 'dev';
            }
        }

        return 'dev';
    }

    public function config(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $this->configRepository->get('vandar', []);
        }

        return $this->configRepository->get("vandar.{$key}", $default);
    }

    public function baseUrl(string $name = 'api'): string
    {
        $baseUrls = $this->config('base_urls', []);

        if (! is_array($baseUrls) || ! array_key_exists($name, $baseUrls)) {
            throw new VandarException(sprintf('Base URL [%s] is not configured.', $name));
        }

        $baseUrl = $baseUrls[$name];

        if (! is_string($baseUrl) || $baseUrl === '') {
            throw new VandarException(sprintf('Base URL [%s] must be a non-empty string.', $name));
        }

        return $baseUrl;
    }

    public function isLoggingEnabled(): bool
    {
        return (bool) $this->config('logging.enabled', false);
    }

    public function client(): VandarClient
    {
        return $this->client;
    }

    public function tokens(): TokenManager
    {
        return $this->tokens;
    }

    public function raw(): RawResource
    {
        return $this->raw;
    }

    public function business(): BusinessResource
    {
        return $this->business;
    }

    public function customers(): CustomerResource
    {
        return $this->customers;
    }

    public function cards(): CardResource
    {
        return $this->cards;
    }

    public function ibans(): IbanResource
    {
        return $this->ibans;
    }

    public function inquiries(): InquiryResource
    {
        return $this->inquiries;
    }

    public function ipg(): IpgResource
    {
        return $this->ipg;
    }

    public function refunds(): RefundResource
    {
        return $this->refunds;
    }

    public function settlements(): SettlementResource
    {
        return $this->settlements;
    }

    public function queuedSettlements(): QueuedSettlementResource
    {
        return $this->queuedSettlements;
    }

    public function batchSettlements(): BatchSettlementResource
    {
        return $this->batchSettlements;
    }

    public function avand(): AvandResource
    {
        return $this->avand;
    }

    public function cashIn(): AvandResource
    {
        return $this->avand();
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $headers
     */
    public function response(array $payload = [], int $status = 200, array $headers = []): VandarResponse
    {
        return new VandarResponse($status, $payload, $headers);
    }
}
