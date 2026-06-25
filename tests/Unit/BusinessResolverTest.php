<?php

declare(strict_types=1);

namespace Zarbinco\LaravelVandar\Tests\Unit;

use Zarbinco\LaravelVandar\Exceptions\VandarBusinessNotConfiguredException;
use Zarbinco\LaravelVandar\Support\BusinessResolver;
use Zarbinco\LaravelVandar\Tests\TestCase;

final class BusinessResolverTest extends TestCase
{
    public function test_it_resolves_explicit_business(): void
    {
        $this->assertSame('explicit-business', $this->app->make(BusinessResolver::class)->resolve('explicit-business'));
    }

    public function test_it_trims_business_values(): void
    {
        $this->assertSame('trimmed-business', $this->app->make(BusinessResolver::class)->resolve('  trimmed-business  '));
    }

    public function test_it_resolves_config_business(): void
    {
        config()->set('vandar.business', 'config-business');

        $this->assertSame('config-business', $this->app->make(BusinessResolver::class)->resolve());
    }

    public function test_explicit_business_overrides_config(): void
    {
        config()->set('vandar.business', 'config-business');

        $this->assertSame('explicit-business', $this->app->make(BusinessResolver::class)->resolve('explicit-business'));
    }

    public function test_missing_business_throws(): void
    {
        config()->set('vandar.business', null);

        $this->expectException(VandarBusinessNotConfiguredException::class);

        $this->app->make(BusinessResolver::class)->resolve();
    }

    public function test_empty_business_throws(): void
    {
        config()->set('vandar.business', '   ');

        $this->expectException(VandarBusinessNotConfiguredException::class);

        $this->app->make(BusinessResolver::class)->resolve();
    }
}
