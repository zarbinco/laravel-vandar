<?php

declare(strict_types=1);

namespace Zarbinco\LaravelVandar\Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Zarbinco\LaravelVandar\Tests\TestCase;

final class AboutCommandTest extends TestCase
{
    public function test_about_command_displays_safe_package_information(): void
    {
        config()->set('vandar.business', 'fake-business');
        config()->set('vandar.tokens.access_token', 'fake-access-token');
        config()->set('vandar.tokens.refresh_token', 'fake-refresh-token');
        config()->set('vandar.tokens.store', 'cache');
        config()->set('vandar.logging.enabled', true);

        $exitCode = Artisan::call('vandar:about');
        $output = Artisan::output();

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('Laravel Vandar SDK', $output);
        $this->assertStringContainsString('Business: configured', $output);
        $this->assertStringContainsString('Base URL keys:', $output);
        $this->assertStringContainsString('Token store driver: cache', $output);
        $this->assertStringContainsString('Logging: enabled', $output);
        $this->assertStringNotContainsString('fake-access-token', $output);
        $this->assertStringNotContainsString('fake-refresh-token', $output);
    }
}
