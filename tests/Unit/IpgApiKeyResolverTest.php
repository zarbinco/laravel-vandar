<?php

declare(strict_types=1);

namespace Zarbinco\LaravelVandar\Tests\Unit;

use Illuminate\Config\Repository;
use PHPUnit\Framework\TestCase;
use Zarbinco\LaravelVandar\Exceptions\VandarIpgApiKeyNotConfiguredException;
use Zarbinco\LaravelVandar\Support\IpgApiKeyResolver;

final class IpgApiKeyResolverTest extends TestCase
{
    public function test_it_resolves_explicit_api_key(): void
    {
        $resolver = $this->resolver('fake-config-ipg-api-key');

        $this->assertSame('fake-explicit-ipg-api-key', $resolver->resolve('fake-explicit-ipg-api-key'));
    }

    public function test_it_trims_explicit_api_key(): void
    {
        $resolver = $this->resolver('fake-config-ipg-api-key');

        $this->assertSame('fake-explicit-ipg-api-key', $resolver->resolve('  fake-explicit-ipg-api-key  '));
    }

    public function test_it_resolves_payload_api_key(): void
    {
        $resolver = $this->resolver('fake-config-ipg-api-key');

        $this->assertSame('fake-payload-ipg-api-key', $resolver->resolve(null, [
            'api_key' => 'fake-payload-ipg-api-key',
        ]));
    }

    public function test_it_resolves_config_api_key(): void
    {
        $resolver = $this->resolver('fake-config-ipg-api-key');

        $this->assertSame('fake-config-ipg-api-key', $resolver->resolve());
    }

    public function test_explicit_api_key_overrides_payload_and_config(): void
    {
        $resolver = $this->resolver('fake-config-ipg-api-key');

        $this->assertSame('fake-explicit-ipg-api-key', $resolver->resolve('fake-explicit-ipg-api-key', [
            'api_key' => 'fake-payload-ipg-api-key',
        ]));
    }

    public function test_payload_api_key_overrides_config(): void
    {
        $resolver = $this->resolver('fake-config-ipg-api-key');

        $this->assertSame('fake-payload-ipg-api-key', $resolver->resolve(null, [
            'api_key' => 'fake-payload-ipg-api-key',
        ]));
    }

    public function test_missing_api_key_throws(): void
    {
        $this->expectException(VandarIpgApiKeyNotConfiguredException::class);

        $this->resolver(null)->resolve();
    }

    public function test_exception_message_does_not_contain_fake_api_key(): void
    {
        try {
            $this->resolver(null)->resolve(' ', ['api_key' => ' ']);
        } catch (VandarIpgApiKeyNotConfiguredException $exception) {
            $this->assertStringNotContainsString('fake-ipg-api-key', $exception->getMessage());

            return;
        }

        $this->fail('Expected missing IPG API key exception was not thrown.');
    }

    private function resolver(?string $apiKey): IpgApiKeyResolver
    {
        return new IpgApiKeyResolver(new Repository([
            'vandar' => [
                'ipg' => [
                    'api_key' => $apiKey,
                ],
            ],
        ]));
    }
}
