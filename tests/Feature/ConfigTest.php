<?php

declare(strict_types=1);

namespace Zarbinco\LaravelVandar\Tests\Feature;

use Zarbinco\LaravelVandar\Exceptions\VandarException;
use Zarbinco\LaravelVandar\LaravelVandar;
use Zarbinco\LaravelVandar\Tests\TestCase;

final class ConfigTest extends TestCase
{
    public function test_default_api_base_url_is_configured(): void
    {
        $this->assertSame('https://api.vandar.io', config('vandar.base_urls.api'));
    }

    public function test_root_object_returns_default_api_base_url(): void
    {
        $vandar = $this->app->make(LaravelVandar::class);

        $this->assertSame('https://api.vandar.io', $vandar->baseUrl('api'));
    }

    public function test_package_logging_is_disabled_by_default(): void
    {
        $vandar = $this->app->make(LaravelVandar::class);

        $this->assertFalse($vandar->isLoggingEnabled());
        $this->assertFalse(config('vandar.logging.enabled'));
    }

    public function test_unknown_base_url_key_throws_exception(): void
    {
        $this->expectException(VandarException::class);

        $this->app->make(LaravelVandar::class)->baseUrl('unknown');
    }
}
