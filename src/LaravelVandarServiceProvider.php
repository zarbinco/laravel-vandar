<?php

declare(strict_types=1);

namespace Zarbinco\LaravelVandar;

use Illuminate\Support\ServiceProvider;
use Zarbinco\LaravelVandar\Commands\VandarAboutCommand;

final class LaravelVandarServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/vandar.php', 'vandar');

        $this->app->singleton('vandar', fn ($app): LaravelVandar => new LaravelVandar($app['config']));
        $this->app->alias('vandar', LaravelVandar::class);
    }

    public function boot(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->publishes([
            __DIR__.'/../config/vandar.php' => config_path('vandar.php'),
        ], 'vandar-config');

        $this->commands([
            VandarAboutCommand::class,
        ]);
    }
}
