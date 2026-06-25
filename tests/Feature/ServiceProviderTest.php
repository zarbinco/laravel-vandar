<?php

declare(strict_types=1);

namespace Zarbinco\LaravelVandar\Tests\Feature;

use Zarbinco\LaravelVandar\Contracts\TokenStore;
use Zarbinco\LaravelVandar\Facades\Vandar;
use Zarbinco\LaravelVandar\Http\VandarClient;
use Zarbinco\LaravelVandar\LaravelVandar;
use Zarbinco\LaravelVandar\Resources\BusinessResource;
use Zarbinco\LaravelVandar\Resources\CustomerFieldResource;
use Zarbinco\LaravelVandar\Resources\CustomerResource;
use Zarbinco\LaravelVandar\Resources\RawResource;
use Zarbinco\LaravelVandar\Tests\TestCase;
use Zarbinco\LaravelVandar\Token\TokenManager;

final class ServiceProviderTest extends TestCase
{
    public function test_it_registers_the_vandar_singleton(): void
    {
        $this->assertTrue($this->app->bound('vandar'));
        $this->assertInstanceOf(LaravelVandar::class, $this->app->make('vandar'));
    }

    public function test_it_resolves_laravel_vandar_class_to_the_singleton(): void
    {
        $root = $this->app->make('vandar');

        $this->assertSame($root, $this->app->make(LaravelVandar::class));
    }

    public function test_facade_resolves_the_package_root_object(): void
    {
        $this->assertSame('Laravel Vandar SDK', Vandar::name());
    }

    public function test_it_registers_phase_two_services(): void
    {
        $this->assertInstanceOf(VandarClient::class, $this->app->make(VandarClient::class));
        $this->assertInstanceOf(TokenManager::class, $this->app->make(TokenManager::class));
        $this->assertInstanceOf(TokenStore::class, $this->app->make(TokenStore::class));
        $this->assertInstanceOf(RawResource::class, $this->app->make(RawResource::class));
    }

    public function test_it_registers_phase_three_services(): void
    {
        $this->assertInstanceOf(BusinessResource::class, $this->app->make(BusinessResource::class));
        $this->assertInstanceOf(CustomerResource::class, $this->app->make(CustomerResource::class));
        $this->assertInstanceOf(CustomerFieldResource::class, $this->app->make(CustomerFieldResource::class));
        $this->assertInstanceOf(BusinessResource::class, Vandar::business());
        $this->assertInstanceOf(CustomerResource::class, Vandar::customers());
    }
}
