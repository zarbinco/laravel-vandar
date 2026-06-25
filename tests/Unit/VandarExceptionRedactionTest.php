<?php

declare(strict_types=1);

namespace Zarbinco\LaravelVandar\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Zarbinco\LaravelVandar\Exceptions\VandarRequestException;

final class VandarExceptionRedactionTest extends TestCase
{
    public function test_request_exception_context_redacts_sensitive_values_and_sanitizes_urls(): void
    {
        $exception = new VandarRequestException(
            message: 'Request failed with fake-access-token and fake-refresh-token.',
            status: 400,
            response: [
                'url' => 'https://api.vandar.io/path?token=fake-query-token&normal=yes',
                'access_token' => 'fake-access-token',
                'refresh_token' => 'fake-refresh-token',
                'headers' => [
                    'Authorization' => 'Bearer fake-authorization-token',
                ],
            ],
        );

        $context = json_encode($exception->context());

        $this->assertIsString($context);
        $this->assertStringNotContainsString('fake-access-token', $context);
        $this->assertStringNotContainsString('fake-refresh-token', $context);
        $this->assertStringNotContainsString('fake-authorization-token', $context);
        $this->assertStringNotContainsString('fake-query-token', $context);
        $this->assertStringContainsString('token=%5Bredacted%5D', $context);
        $this->assertStringContainsString('normal=yes', $context);
        $this->assertStringNotContainsString('fake-access-token', $exception->getMessage());
        $this->assertStringNotContainsString('fake-refresh-token', $exception->getMessage());
    }
}
