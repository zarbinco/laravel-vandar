<?php

declare(strict_types=1);

namespace Zarbinco\LaravelVandar\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Zarbinco\LaravelVandar\Facades\Vandar;
use Zarbinco\LaravelVandar\LaravelVandarServiceProvider;

abstract class TestCase extends Orchestra
{
    /**
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app)
    {
        return [
            LaravelVandarServiceProvider::class,
        ];
    }

    /**
     * @return array<string, class-string>
     */
    protected function getPackageAliases($app)
    {
        return [
            'Vandar' => Vandar::class,
        ];
    }
}
