<?php

declare(strict_types=1);

namespace Zarbinco\LaravelVandar;

use Illuminate\Support\ServiceProvider;
use Zarbinco\LaravelVandar\Commands\VandarAboutCommand;
use Zarbinco\LaravelVandar\Commands\VandarRefreshTokenCommand;
use Zarbinco\LaravelVandar\Contracts\TokenStore;
use Zarbinco\LaravelVandar\Http\PendingVandarRequest;
use Zarbinco\LaravelVandar\Http\VandarClient;
use Zarbinco\LaravelVandar\Resources\BusinessResource;
use Zarbinco\LaravelVandar\Resources\CustomerFieldResource;
use Zarbinco\LaravelVandar\Resources\CustomerResource;
use Zarbinco\LaravelVandar\Resources\RawResource;
use Zarbinco\LaravelVandar\Support\BusinessResolver;
use Zarbinco\LaravelVandar\Token\CacheTokenStore;
use Zarbinco\LaravelVandar\Token\ConfigTokenStore;
use Zarbinco\LaravelVandar\Token\TokenManager;
use Zarbinco\LaravelVandar\Token\TokenStoreManager;

final class LaravelVandarServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/vandar.php', 'vandar');

        $this->app->singleton(ConfigTokenStore::class);
        $this->app->singleton(CacheTokenStore::class);
        $this->app->singleton(TokenStoreManager::class);
        $this->app->singleton(TokenStore::class, TokenStoreManager::defaultResolver());
        $this->app->singleton(PendingVandarRequest::class);
        $this->app->singleton(VandarClient::class);
        $this->app->singleton(TokenManager::class);
        $this->app->singleton(RawResource::class);
        $this->app->singleton(BusinessResolver::class);
        $this->app->singleton(BusinessResource::class);
        $this->app->singleton(CustomerFieldResource::class);
        $this->app->singleton(CustomerResource::class);

        $this->app->singleton('vandar', fn ($app): LaravelVandar => new LaravelVandar(
            $app['config'],
            $app->make(VandarClient::class),
            $app->make(TokenManager::class),
            $app->make(RawResource::class),
            $app->make(BusinessResource::class),
            $app->make(CustomerResource::class),
        ));
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
            VandarRefreshTokenCommand::class,
        ]);
    }
}
