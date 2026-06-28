<?php

declare(strict_types=1);

namespace Zarbinco\LaravelVandar\Tests\Unit;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;
use Zarbinco\LaravelVandar\DTO\TokenSet;
use Zarbinco\LaravelVandar\Tests\TestCase;
use Zarbinco\LaravelVandar\Token\CacheTokenStore;
use Zarbinco\LaravelVandar\Token\ConfigTokenStore;

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

    public function test_config_fallback_respects_real_access_token_expiry(): void
    {
        $expiresAt = CarbonImmutable::parse('2026-07-01T12:30:00+00:00');
        config()->set('vandar.tokens.access_token', 'fake-config-access-token');
        config()->set('vandar.tokens.refresh_token', 'fake-config-refresh-token');
        config()->set('vandar.tokens.access_token_ttl_seconds', 60);
        config()->set('vandar.tokens.access_token_expires_at', $expiresAt->toIso8601String());

        $tokens = $this->app->make(CacheTokenStore::class)->tokens();

        $this->assertNotNull($tokens);
        $this->assertTrue($expiresAt->equalTo($tokens->expiresAt));
    }

    public function test_cached_tokens_are_preferred_over_config_fallback_tokens(): void
    {
        config()->set('vandar.tokens.access_token', 'fake-config-access-token');
        config()->set('vandar.tokens.refresh_token', 'fake-config-refresh-token');

        $store = $this->app->make(CacheTokenStore::class);
        $store->save(new TokenSet('Bearer', 'fake-cache-access-token', 'fake-cache-refresh-token'));

        $tokens = $store->tokens();

        $this->assertNotNull($tokens);
        $this->assertSame('fake-cache-access-token', $tokens->accessToken);
        $this->assertSame('fake-cache-refresh-token', $tokens->refreshToken);
    }

    public function test_config_fallback_is_not_persisted_to_cache_by_default(): void
    {
        config()->set('vandar.tokens.access_token', 'fake-config-access-token');
        config()->set('vandar.tokens.refresh_token', 'fake-config-refresh-token');

        $tokens = $this->app->make(CacheTokenStore::class)->tokens();

        $this->assertNotNull($tokens);
        $this->assertSame('fake-config-access-token', $tokens->accessToken);
        $this->assertNull(Cache::get('vandar.cache-store-test.tokens'));
    }

    public function test_config_fallback_can_be_persisted_to_cache_on_first_read(): void
    {
        config()->set('vandar.tokens.encrypt_cache', false);
        config()->set('vandar.tokens.persist_config_fallback_to_cache', true);
        config()->set('vandar.tokens.access_token', 'fake-config-access-token');
        config()->set('vandar.tokens.refresh_token', 'fake-config-refresh-token');

        $tokens = $this->app->make(CacheTokenStore::class)->tokens();
        $cached = Cache::get('vandar.cache-store-test.tokens');

        $this->assertNotNull($tokens);
        $this->assertIsArray($cached);
        $this->assertSame('fake-config-access-token', $cached['access_token'] ?? null);
        $this->assertSame('fake-config-refresh-token', $cached['refresh_token'] ?? null);
    }

    public function test_persisted_config_fallback_preserves_expiry_and_is_preferred_on_future_reads(): void
    {
        $expiresAt = CarbonImmutable::parse('2026-07-01T12:30:00+00:00');
        config()->set('vandar.tokens.encrypt_cache', false);
        config()->set('vandar.tokens.persist_config_fallback_to_cache', true);
        config()->set('vandar.tokens.access_token', 'fake-config-access-token');
        config()->set('vandar.tokens.refresh_token', 'fake-config-refresh-token');
        config()->set('vandar.tokens.access_token_expires_at', $expiresAt->timestamp);

        $firstRead = $this->app->make(CacheTokenStore::class)->tokens();

        config()->set('vandar.tokens.access_token', 'fake-changed-config-access-token');
        config()->set('vandar.tokens.refresh_token', 'fake-changed-config-refresh-token');

        $secondRead = $this->app->make(CacheTokenStore::class)->tokens();
        $cached = Cache::get('vandar.cache-store-test.tokens');

        $this->assertNotNull($firstRead);
        $this->assertNotNull($secondRead);
        $this->assertSame('fake-config-access-token', $secondRead->accessToken);
        $this->assertTrue($expiresAt->equalTo($secondRead->expiresAt));
        $this->assertIsArray($cached);
        $this->assertTrue($expiresAt->equalTo(CarbonImmutable::parse((string) ($cached['expires_at'] ?? ''))));
    }

    public function test_config_store_does_not_persist_fallback_tokens_to_cache(): void
    {
        config()->set('vandar.tokens.store', 'config');
        config()->set('vandar.tokens.persist_config_fallback_to_cache', true);
        config()->set('vandar.tokens.access_token', 'fake-config-access-token');
        config()->set('vandar.tokens.refresh_token', 'fake-config-refresh-token');

        $tokens = $this->app->make(ConfigTokenStore::class)->tokens();

        $this->assertNotNull($tokens);
        $this->assertSame('fake-config-access-token', $tokens->accessToken);
        $this->assertNull(Cache::get('vandar.cache-store-test.tokens'));
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
