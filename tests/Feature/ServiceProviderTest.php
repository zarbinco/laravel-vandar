<?php

declare(strict_types=1);

namespace Zarbinco\LaravelVandar\Tests\Feature;

use Zarbinco\LaravelVandar\Facades\Vandar;
use Zarbinco\LaravelVandar\LaravelVandar;
use Zarbinco\LaravelVandar\Tests\TestCase;

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
}
