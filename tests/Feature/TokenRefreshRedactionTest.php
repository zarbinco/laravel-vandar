<?php

declare(strict_types=1);

namespace Zarbinco\LaravelVandar\Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Zarbinco\LaravelVandar\Exceptions\VandarTokenRefreshException;
use Zarbinco\LaravelVandar\Tests\TestCase;
use Zarbinco\LaravelVandar\Token\TokenManager;

final class TokenRefreshRedactionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        config()->set('vandar.tokens.store', 'cache');
        config()->set('vandar.tokens.cache_key', 'vandar.redaction-test.tokens');
        config()->set('vandar.tokens.access_token', 'fake-access-token');
        config()->set('vandar.tokens.refresh_token', 'fake-refresh-token');
    }

    public function test_failed_refresh_exception_does_not_expose_response_tokens(): void
    {
        Http::fake([
            'https://api.vandar.io/v3/refreshtoken' => Http::response([
                'message' => 'Denied fake-access-token fake-refresh-token.',
                'access_token' => 'fake-access-token',
                'refresh_token' => 'fake-refresh-token',
            ], 401),
        ]);

        try {
            $this->app->make(TokenManager::class)->refresh(force: true);
        } catch (VandarTokenRefreshException $exception) {
            $context = json_encode($exception->context());

            $this->assertIsString($context);
            $this->assertStringNotContainsString('fake-access-token', $exception->getMessage());
            $this->assertStringNotContainsString('fake-refresh-token', $exception->getMessage());
            $this->assertStringNotContainsString('fake-access-token', $context);
            $this->assertStringNotContainsString('fake-refresh-token', $context);
            $this->assertStringContainsString('[redacted]', $context);

            return;
        }

        $this->fail('Expected Vandar token refresh exception was not thrown.');
    }

    public function test_failed_refresh_command_output_does_not_expose_response_tokens(): void
    {
        Http::fake([
            'https://api.vandar.io/v3/refreshtoken' => Http::response([
                'message' => 'Denied fake-access-token fake-refresh-token.',
                'access_token' => 'fake-access-token',
                'refresh_token' => 'fake-refresh-token',
            ], 401),
        ]);

        $exitCode = Artisan::call('vandar:refresh-token');
        $output = Artisan::output();

        $this->assertSame(1, $exitCode);
        $this->assertStringContainsString('Unable to refresh Vandar token', $output);
        $this->assertStringNotContainsString('fake-access-token', $output);
        $this->assertStringNotContainsString('fake-refresh-token', $output);
    }
}
