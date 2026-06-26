<?php

declare(strict_types=1);

namespace Zarbinco\LaravelVandar\Token;

use Closure;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Container\Container;
use Zarbinco\LaravelVandar\Contracts\TokenStore;
use Zarbinco\LaravelVandar\Exceptions\VandarUnsupportedTokenStoreException;

final class TokenStoreManager
{
    private static ?Closure $defaultResolver = null;

    public function __construct(
        private readonly Container $app,
        private readonly ConfigRepository $config,
    ) {}

    public static function defaultResolver(): Closure
    {
        return self::$defaultResolver ??= static fn ($app): TokenStore => $app->make(self::class)->store();
    }

    public function store(): TokenStore
    {
        $driver = $this->driverName();

        return match ($driver) {
            'config' => $this->app->make(ConfigTokenStore::class),
            'cache' => $this->app->make(CacheTokenStore::class),
            'custom' => $this->customStore(),
            default => throw new VandarUnsupportedTokenStoreException("Unsupported Vandar token store [{$driver}]."),
        };
    }

    private function customStore(): TokenStore
    {
        if ($this->hasOnlyDefaultTokenStoreBinding()) {
            throw new VandarUnsupportedTokenStoreException('A custom Vandar token store must bind TokenStore::class.');
        }

        if (! $this->app->bound(TokenStore::class)) {
            throw new VandarUnsupportedTokenStoreException('A custom Vandar token store must bind TokenStore::class.');
        }

        $store = $this->app->make($this->tokenStoreAbstract());

        if (! $store instanceof TokenStore) {
            throw new VandarUnsupportedTokenStoreException('The custom Vandar token store binding must implement TokenStore.');
        }

        return $store;
    }

    private function hasOnlyDefaultTokenStoreBinding(): bool
    {
        if (! method_exists($this->app, 'getBindings')) {
            return false;
        }

        $binding = $this->app->getBindings()[TokenStore::class] ?? null;

        return ($binding['concrete'] ?? null) === self::$defaultResolver;
    }

    private function tokenStoreAbstract(): string
    {
        return TokenStore::class;
    }

    private function driverName(): string
    {
        $driver = $this->config->get('vandar.tokens.store', 'cache');

        return is_string($driver) && $driver !== '' ? strtolower($driver) : 'cache';
    }
}
