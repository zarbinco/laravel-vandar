<?php

declare(strict_types=1);

namespace Zarbinco\LaravelVandar\Commands;

use Illuminate\Console\Command;
use Zarbinco\LaravelVandar\LaravelVandar;

final class VandarAboutCommand extends Command
{
    protected $signature = 'vandar:about';

    protected $description = 'Display Laravel Vandar package information.';

    public function handle(LaravelVandar $vandar): int
    {
        $baseUrls = $vandar->config('base_urls', []);
        $baseUrlKeys = is_array($baseUrls) ? implode(', ', array_keys($baseUrls)) : 'none';
        $business = $vandar->config('business');
        $businessStatus = is_string($business) && $business !== '' ? 'configured' : 'not configured';
        $tokenStore = $vandar->config('tokens.store', 'cache');
        $tokenStore = is_scalar($tokenStore) ? (string) $tokenStore : 'not configured';
        $accessTokenStatus = $this->configuredStatus($vandar->config('tokens.access_token'));
        $refreshTokenStatus = $this->configuredStatus($vandar->config('tokens.refresh_token'));
        $cacheEncryption = (bool) $vandar->config('tokens.encrypt_cache', true) ? 'enabled' : 'disabled';
        $loggingStatus = $vandar->isLoggingEnabled() ? 'enabled' : 'disabled';

        $this->line('Package name: '.$vandar->name());
        $this->line('Package version: '.$vandar->version());
        $this->line('Business: '.$businessStatus);
        $this->line('Base URL keys: '.$baseUrlKeys);
        $this->line('Token store driver: '.$tokenStore);
        $this->line('Access token configured: '.$accessTokenStatus);
        $this->line('Refresh token configured: '.$refreshTokenStatus);
        $this->line('Cache encryption: '.$cacheEncryption);
        $this->line('Logging: '.$loggingStatus);

        return self::SUCCESS;
    }

    private function configuredStatus(mixed $value): string
    {
        return is_string($value) && $value !== '' ? 'yes' : 'no';
    }
}
