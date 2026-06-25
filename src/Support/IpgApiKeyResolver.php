<?php

declare(strict_types=1);

namespace Zarbinco\LaravelVandar\Support;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Zarbinco\LaravelVandar\Exceptions\VandarIpgApiKeyNotConfiguredException;

final class IpgApiKeyResolver
{
    public function __construct(
        private readonly ConfigRepository $config,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public function resolve(?string $apiKey = null, array $payload = []): string
    {
        foreach ([$apiKey, $payload['api_key'] ?? null, $this->config->get('vandar.ipg.api_key')] as $candidate) {
            if (! is_string($candidate)) {
                continue;
            }

            $candidate = trim($candidate);

            if ($candidate !== '') {
                return $candidate;
            }
        }

        throw new VandarIpgApiKeyNotConfiguredException;
    }
}
