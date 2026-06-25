<?php

declare(strict_types=1);

namespace Zarbinco\LaravelVandar\Tests\Unit;

use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use Zarbinco\LaravelVandar\Contracts\TokenStore;
use Zarbinco\LaravelVandar\DTO\TokenSet;
use Zarbinco\LaravelVandar\Exceptions\VandarUnsupportedTokenStoreException;
use Zarbinco\LaravelVandar\Tests\TestCase;
use Zarbinco\LaravelVandar\Token\CacheTokenStore;
use Zarbinco\LaravelVandar\Token\ConfigTokenStore;
use Zarbinco\LaravelVandar\Token\TokenStoreManager;

final class TokenStoreManagerTest extends TestCase
{
    public function test_it_resolves_config_store(): void
    {
        config()->set('vandar.tokens.store', 'config');

        $this->assertInstanceOf(ConfigTokenStore::class, $this->app->make(TokenStoreManager::class)->store());
    }

    public function test_it_resolves_cache_store(): void
    {
        config()->set('vandar.tokens.store', 'cache');

        $this->assertInstanceOf(CacheTokenStore::class, $this->app->make(TokenStoreManager::class)->store());
    }

    public function test_unknown_driver_throws(): void
    {
        config()->set('vandar.tokens.store', 'database');

        $this->expectException(VandarUnsupportedTokenStoreException::class);

        $this->app->make(TokenStoreManager::class)->store();
    }

    public function test_custom_driver_requires_binding(): void
    {
        $app = new Container;
        $config = new Repository(['vandar' => ['tokens' => ['store' => 'custom']]]);
        $manager = new TokenStoreManager($app, $config);

        $this->expectException(VandarUnsupportedTokenStoreException::class);

        $manager->store();
    }

    public function test_package_default_token_store_binding_does_not_satisfy_custom_driver(): void
    {
        config()->set('vandar.tokens.store', 'custom');

        $this->expectException(VandarUnsupportedTokenStoreException::class);

        $this->app->make(TokenStore::class);
    }

    public function test_custom_driver_resolves_bound_token_store(): void
    {
        $app = new Container;
        $config = new Repository(['vandar' => ['tokens' => ['store' => 'custom']]]);
        $store = new class implements TokenStore
        {
            public function accessToken(): ?string
            {
                return 'fake-access-token';
            }

            public function refreshToken(): ?string
            {
                return 'fake-refresh-token';
            }

            public function save(TokenSet $tokens): void
            {
                //
            }

            public function clear(): void
            {
                //
            }
        };

        $app->instance(TokenStore::class, $store);

        $this->assertSame($store, (new TokenStoreManager($app, $config))->store());
    }
}
