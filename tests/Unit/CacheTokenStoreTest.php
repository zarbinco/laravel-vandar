<?php

declare(strict_types=1);

namespace Zarbinco\LaravelVandar\Tests\Unit;

use Illuminate\Support\Facades\Cache;
use Zarbinco\LaravelVandar\DTO\TokenSet;
use Zarbinco\LaravelVandar\Tests\TestCase;
use Zarbinco\LaravelVandar\Token\CacheTokenStore;

final class CacheTokenStoreTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        config()->set('vandar.tokens.cache_key', 'vandar.cache-store-test.tokens');
        config()->set('vandar.tokens.encrypt_cache', true);
    }

    public function test_it_falls_back_to_config_tokens_when_cache_is_empty(): void
    {
        config()->set('vandar.tokens.access_token', 'fake-config-access-token');
        config()->set('vandar.tokens.refresh_token', 'fake-config-refresh-token');

        $tokens = $this->app->make(CacheTokenStore::class)->tokens();

        $this->assertSame('fake-config-access-token', $tokens->accessToken);
        $this->assertSame('fake-config-refresh-token', $tokens->refreshToken);
    }

    public function test_it_saves_and_reads_tokens(): void
    {
        $store = $this->app->make(CacheTokenStore::class);

        $store->save(new TokenSet('Bearer', 'fake-cache-access-token', 'fake-cache-refresh-token'));

        $this->assertSame('fake-cache-access-token', $store->accessToken());
        $this->assertSame('fake-cache-refresh-token', $store->refreshToken());
    }

    public function test_it_uses_the_configured_cache_key_without_touching_other_keys(): void
    {
        config()->set('vandar.tokens.cache_key', 'tenant-a:vandar:tokens');
        Cache::put('tenant-b:vandar:tokens', [
            'token_type' => 'Bearer',
            'access_token' => 'fake-other-access-token',
            'refresh_token' => 'fake-other-refresh-token',
        ]);

        $this->app->make(CacheTokenStore::class)
            ->save(new TokenSet('Bearer', 'fake-tenant-access-token', 'fake-tenant-refresh-token'));

        $this->assertSame('fake-tenant-access-token', $this->app->make(CacheTokenStore::class)->accessToken());
        $this->assertSame('fake-other-access-token', Cache::get('tenant-b:vandar:tokens')['access_token'] ?? null);
    }

    public function test_clear_removes_cached_tokens(): void
    {
        config()->set('vandar.tokens.access_token', null);
        config()->set('vandar.tokens.refresh_token', null);

        $store = $this->app->make(CacheTokenStore::class);
        $store->save(new TokenSet('Bearer', 'fake-cache-access-token', 'fake-cache-refresh-token'));
        $store->clear();

        $this->assertNull($store->tokens());
    }

    public function test_encrypted_cache_payload_does_not_contain_raw_tokens(): void
    {
        $this->app->make(CacheTokenStore::class)
            ->save(new TokenSet('Bearer', 'fake-encrypted-access-token', 'fake-encrypted-refresh-token'));

        $raw = Cache::get('vandar.cache-store-test.tokens');

        $this->assertIsString($raw);
        $this->assertStringNotContainsString('fake-encrypted-access-token', $raw);
        $this->assertStringNotContainsString('fake-encrypted-refresh-token', $raw);
    }
}
