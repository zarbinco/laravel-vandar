<?php

declare(strict_types=1);

namespace Zarbinco\LaravelVandar\Support;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Zarbinco\LaravelVandar\Exceptions\VandarBusinessNotConfiguredException;

final class BusinessResolver
{
    public function __construct(
        private readonly ConfigRepository $config,
    ) {}

    public function resolve(?string $business = null): string
    {
        $resolved = $business ?? $this->config->get('vandar.business');

        if (! is_string($resolved)) {
            throw new VandarBusinessNotConfiguredException;
        }

        $resolved = trim($resolved);

        if ($resolved === '') {
            throw new VandarBusinessNotConfiguredException;
        }

        return $resolved;
    }

    public function segment(?string $business = null): string
    {
        return VandarPath::segment($this->resolve($business));
    }
}
