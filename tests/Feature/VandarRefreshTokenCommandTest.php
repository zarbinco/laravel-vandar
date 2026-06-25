<?php

declare(strict_types=1);

namespace Zarbinco\LaravelVandar\Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Zarbinco\LaravelVandar\Tests\TestCase;

final class VandarRefreshTokenCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        config()->set('vandar.tokens.store', 'cache');
        config()->set('vandar.tokens.cache_key', 'vandar.command.tokens');
        config()->set('vandar.tokens.access_token', 'fake-command-access-token');
        config()->set('vandar.tokens.refresh_token', 'fake-command-refresh-token');
    }

    public function test_refresh_token_command_succeeds_without_printing_tokens(): void
    {
        Http::fake([
            'https://api.vandar.io/v3/refreshtoken' => Http::response([
                'token_type' => 'Bearer',
                'access_token' => 'fake-command-new-access-token',
                'refresh_token' => 'fake-command-new-refresh-token',
                'expires_in' => 3600,
            ], 200),
        ]);

        $exitCode = Artisan::call('vandar:refresh-token');
        $output = Artisan::output();

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('Vandar token refreshed successfully.', $output);
        $this->assertStringContainsString('Token type: Bearer', $output);
        $this->assertStringNotContainsString('fake-command-access-token', $output);
        $this->assertStringNotContainsString('fake-command-refresh-token', $output);
        $this->assertStringNotContainsString('fake-command-new-access-token', $output);
        $this->assertStringNotContainsString('fake-command-new-refresh-token', $output);
    }

    public function test_failed_refresh_token_command_does_not_print_tokens(): void
    {
        Http::fake([
            'https://api.vandar.io/v3/refreshtoken' => Http::response([
                'message' => 'Unauthorized',
                'access_token' => 'fake-command-response-access-token',
                'refresh_token' => 'fake-command-response-refresh-token',
            ], 401),
        ]);

        $exitCode = Artisan::call('vandar:refresh-token');
        $output = Artisan::output();

        $this->assertSame(1, $exitCode);
        $this->assertStringContainsString('Unable to refresh Vandar token', $output);
        $this->assertStringNotContainsString('fake-command-access-token', $output);
        $this->assertStringNotContainsString('fake-command-refresh-token', $output);
        $this->assertStringNotContainsString('fake-command-response-access-token', $output);
        $this->assertStringNotContainsString('fake-command-response-refresh-token', $output);
    }
}
