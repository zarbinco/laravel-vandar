<?php

declare(strict_types=1);

namespace Zarbinco\LaravelVandar;

use Composer\InstalledVersions;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Zarbinco\LaravelVandar\Exceptions\VandarException;

final class LaravelVandar
{
    public function __construct(
        private readonly ConfigRepository $configRepository,
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
}
