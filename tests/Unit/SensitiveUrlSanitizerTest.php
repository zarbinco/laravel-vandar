<?php

declare(strict_types=1);

namespace Zarbinco\LaravelVandar\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Zarbinco\LaravelVandar\Support\SensitiveUrlSanitizer;

final class SensitiveUrlSanitizerTest extends TestCase
{
    public function test_absolute_url_with_token_query_is_redacted(): void
    {
        $url = SensitiveUrlSanitizer::sanitize('https://api.vandar.io/path?token=fake-query-token&normal=yes');

        $this->assertSame('https://api.vandar.io/path?token=%5Bredacted%5D&normal=yes', $url);
    }

    public function test_relative_url_with_refreshtoken_query_is_redacted(): void
    {
        $url = SensitiveUrlSanitizer::sanitize('/v3/refreshtoken?refreshtoken=fake-refresh-token');

        $this->assertSame('/v3/refreshtoken?refreshtoken=%5Bredacted%5D', $url);
    }

    public function test_non_sensitive_query_values_remain_unchanged(): void
    {
        $url = SensitiveUrlSanitizer::sanitize('https://api.vandar.io/path?normal=yes&status=ok');

        $this->assertSame('https://api.vandar.io/path?normal=yes&status=ok', $url);
    }

    public function test_key_matching_is_case_insensitive(): void
    {
        $url = SensitiveUrlSanitizer::sanitize('/path?Access_Token=fake-access-token&NORMAL=yes');

        $this->assertSame('/path?Access_Token=%5Bredacted%5D&NORMAL=yes', $url);
    }

    public function test_multiple_sensitive_query_keys_are_redacted(): void
    {
        $url = SensitiveUrlSanitizer::sanitize('/path?token=fake-query-token&refresh_token=fake-refresh-token&normal=yes');

        $this->assertSame('/path?token=%5Bredacted%5D&refresh_token=%5Bredacted%5D&normal=yes', $url);
    }

    public function test_malformed_url_returns_safe_value(): void
    {
        $url = SensitiveUrlSanitizer::sanitize('http://[::1');

        $this->assertSame('[redacted-url]', $url);
    }
}
