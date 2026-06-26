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

    public function test_settlement_query_keys_are_redacted(): void
    {
        $url = SensitiveUrlSanitizer::sanitize('/path?track_id=fake-track-id&amount=100000&settlement_id=fake-settlement-id&normal=yes');

        $this->assertSame('/path?track_id=%5Bredacted%5D&amount=%5Bredacted%5D&settlement_id=%5Bredacted%5D&normal=yes', $url);
    }

    public function test_avand_and_settlement_query_keys_are_redacted(): void
    {
        $url = SensitiveUrlSanitizer::sanitize('/path?tracking_code=fake-tracking-code&batchId=fake-batch-id&queued_id=fake-queued-id&suspicious_payment_id=fake-suspicious-id&payment_identifier=fake-payment-id&cash_in_code=fake-code&balance=100000&normal=yes');

        $this->assertSame('/path?tracking_code=%5Bredacted%5D&batchId=%5Bredacted%5D&queued_id=%5Bredacted%5D&suspicious_payment_id=%5Bredacted%5D&payment_identifier=%5Bredacted%5D&cash_in_code=%5Bredacted%5D&balance=%5Bredacted%5D&normal=yes', $url);
    }

    public function test_identity_query_keys_are_redacted(): void
    {
        $url = SensitiveUrlSanitizer::sanitize('/path?nationalCode=fake-national-code&birthDate=fake-birth-date&birthCertificateNumber=fake-certificate&identity_number=fake-identity&identityNumber=fake-identity&normal=yes');

        $this->assertSame('/path?nationalCode=%5Bredacted%5D&birthDate=%5Bredacted%5D&birthCertificateNumber=%5Bredacted%5D&identity_number=%5Bredacted%5D&identityNumber=%5Bredacted%5D&normal=yes', $url);
    }

    public function test_subscription_query_keys_are_redacted(): void
    {
        $url = SensitiveUrlSanitizer::sanitize('/path?authorizationId=fake-authorization&withdrawal_id=fake-withdrawal&refundId=fake-refund&mandate_id=fake-mandate&subscriptionId=fake-subscription&bankAccount=fake-account&accountNumber=fake-account&trackId=fake-track&normal=yes');

        $this->assertSame('/path?authorizationId=%5Bredacted%5D&withdrawal_id=%5Bredacted%5D&refundId=%5Bredacted%5D&mandate_id=%5Bredacted%5D&subscriptionId=%5Bredacted%5D&bankAccount=%5Bredacted%5D&accountNumber=%5Bredacted%5D&trackId=%5Bredacted%5D&normal=yes', $url);
    }

    public function test_sensitive_path_segments_are_redacted_exactly(): void
    {
        $url = SensitiveUrlSanitizer::sanitize(
            '/v4/business/test-business/settlement/fake-track-id',
            sensitivePathSegments: ['fake-track-id'],
        );

        $this->assertSame('/v4/business/test-business/settlement/[redacted]', $url);
    }

    public function test_url_encoded_sensitive_path_segments_are_redacted_after_decoding(): void
    {
        $url = SensitiveUrlSanitizer::sanitize(
            '/v4/business/test-business/settlement/fake%20track%2Fid',
            sensitivePathSegments: ['fake track/id'],
        );

        $this->assertSame('/v4/business/test-business/settlement/[redacted]', $url);
    }

    public function test_sensitive_path_segment_redaction_does_not_replace_substrings(): void
    {
        $url = SensitiveUrlSanitizer::sanitize(
            '/path/prefix-fake-track-id/fake-track-id-suffix/fake-track-id',
            sensitivePathSegments: ['fake-track-id'],
        );

        $this->assertSame('/path/prefix-fake-track-id/fake-track-id-suffix/[redacted]', $url);
    }

    public function test_malformed_url_returns_safe_value(): void
    {
        $url = SensitiveUrlSanitizer::sanitize('http://[::1');

        $this->assertSame('[redacted-url]', $url);
    }
}
