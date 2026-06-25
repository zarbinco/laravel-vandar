<?php

declare(strict_types=1);

namespace Zarbinco\LaravelVandar\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Zarbinco\LaravelVandar\Support\SensitiveDataRedactor;

final class SensitiveDataRedactorTest extends TestCase
{
    public function test_it_redacts_sensitive_keys(): void
    {
        $payload = [
            'access_token' => 'fake-access-token',
            'refresh_token' => 'fake-refresh-token',
            'name' => 'Public Name',
        ];

        $redacted = SensitiveDataRedactor::redact($payload);

        $this->assertSame('[redacted]', $redacted['access_token']);
        $this->assertSame('[redacted]', $redacted['refresh_token']);
        $this->assertSame('Public Name', $redacted['name']);
    }

    public function test_it_redacts_nested_sensitive_values(): void
    {
        $payload = [
            'meta' => [
                'authorization' => 'Bearer fake-token',
                'customer' => [
                    'mobile' => 'fake-mobile',
                ],
            ],
        ];

        $redacted = SensitiveDataRedactor::redact($payload);

        $this->assertSame('[redacted]', $redacted['meta']['authorization']);
        $this->assertSame('[redacted]', $redacted['meta']['customer']['mobile']);
    }

    public function test_it_preserves_non_sensitive_values(): void
    {
        $payload = [
            'amount' => 10000,
            'description' => 'Package foundation test',
            'nested' => [
                'status' => 'ok',
            ],
        ];

        $this->assertSame($payload, SensitiveDataRedactor::redact($payload));
    }

    public function test_extra_sensitive_keys_work(): void
    {
        $payload = [
            'tracking_id' => 'fake-tracking-id',
            'status' => 'ok',
        ];

        $redacted = SensitiveDataRedactor::redact($payload, ['tracking_id']);

        $this->assertSame('[redacted]', $redacted['tracking_id']);
        $this->assertSame('ok', $redacted['status']);
    }

    public function test_key_matching_is_case_insensitive(): void
    {
        $payload = [
            'Access_Token' => 'fake-access-token',
            'CARD_NUMBER' => 'fake-card-number',
            'safe' => true,
        ];

        $redacted = SensitiveDataRedactor::redact($payload);

        $this->assertSame('[redacted]', $redacted['Access_Token']);
        $this->assertSame('[redacted]', $redacted['CARD_NUMBER']);
        $this->assertTrue($redacted['safe']);
    }
}
