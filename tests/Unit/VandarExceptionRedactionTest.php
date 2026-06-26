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
                'url' => 'https://api.vandar.io/path?token=fake-query-token&factorNumber=fake-factor-query&customerId=fake-customer-query&normal=yes',
                'access_token' => 'fake-access-token',
                'refresh_token' => 'fake-refresh-token',
                'body' => '{"factorNumber":"fake-factor-number","customer_id":"fake-customer-id","settlement_id":"fake-settlement-id",',
                'error' => 'Failed for factorNumber fake-factor-number and customer_id fake-customer-id.',
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
        $this->assertStringNotContainsString('fake-factor-query', $context);
        $this->assertStringNotContainsString('fake-customer-query', $context);
        $this->assertStringNotContainsString('fake-factor-number', $context);
        $this->assertStringNotContainsString('fake-customer-id', $context);
        $this->assertStringNotContainsString('fake-settlement-id', $context);
        $this->assertStringContainsString('token=%5Bredacted%5D', $context);
        $this->assertStringContainsString('factorNumber=%5Bredacted%5D', $context);
        $this->assertStringContainsString('customerId=%5Bredacted%5D', $context);
        $this->assertStringContainsString('normal=yes', $context);
        $this->assertStringNotContainsString('fake-access-token', $exception->getMessage());
        $this->assertStringNotContainsString('fake-refresh-token', $exception->getMessage());
    }
}
