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

    public function test_it_redacts_authorization_and_refresh_token_endpoint_keys(): void
    {
        $payload = [
            'Authorization' => 'Bearer fake-access-token',
            'refreshtoken' => 'fake-refresh-token',
        ];

        $redacted = SensitiveDataRedactor::redact($payload);

        $this->assertSame('[redacted]', $redacted['Authorization']);
        $this->assertSame('[redacted]', $redacted['refreshtoken']);
    }

    public function test_it_redacts_api_key_headers_and_nested_authorization_headers(): void
    {
        $payload = [
            'X-Api-Key' => 'fake-api-key',
            'headers' => [
                'Api-Key' => 'fake-api-key',
                'Authorization' => 'Bearer fake-authorization-token',
            ],
        ];

        $redacted = SensitiveDataRedactor::redact($payload);

        $this->assertSame('[redacted]', $redacted['X-Api-Key']);
        $this->assertSame('[redacted]', $redacted['headers']['Api-Key']);
        $this->assertSame('[redacted]', $redacted['headers']['Authorization']);
    }

    public function test_it_redacts_card_iban_and_account_values(): void
    {
        $payload = [
            'card' => 'fake-card',
            'iban' => 'fake-iban',
            'account_number' => 'fake-account-number',
            'safe' => 'visible',
        ];

        $redacted = SensitiveDataRedactor::redact($payload);

        $this->assertSame('[redacted]', $redacted['card']);
        $this->assertSame('[redacted]', $redacted['iban']);
        $this->assertSame('[redacted]', $redacted['account_number']);
        $this->assertSame('visible', $redacted['safe']);
    }

    public function test_it_redacts_inquiry_identity_banking_and_image_values(): void
    {
        $payload = [
            'national_code' => 'fake-national-code',
            'individual_national_code' => 'fake-individual-national-code',
            'legal_national_code' => 'fake-legal-national-code',
            'fida_code' => 'fake-fida-code',
            'birthday' => 'fake-birthday',
            'birth_date' => 'fake-birth-date',
            'postal_code' => 'fake-postal-code',
            'card' => 'fake-card',
            'iban' => 'fake-iban',
            'image' => 'fake-image',
            'images' => ['fake-image'],
            'safe' => 'visible',
        ];

        $redacted = SensitiveDataRedactor::redact($payload);

        $this->assertSame('[redacted]', $redacted['national_code']);
        $this->assertSame('[redacted]', $redacted['individual_national_code']);
        $this->assertSame('[redacted]', $redacted['legal_national_code']);
        $this->assertSame('[redacted]', $redacted['fida_code']);
        $this->assertSame('[redacted]', $redacted['birthday']);
        $this->assertSame('[redacted]', $redacted['birth_date']);
        $this->assertSame('[redacted]', $redacted['postal_code']);
        $this->assertSame('[redacted]', $redacted['card']);
        $this->assertSame('[redacted]', $redacted['iban']);
        $this->assertSame('[redacted]', $redacted['image']);
        $this->assertSame('[redacted]', $redacted['images']);
        $this->assertSame('visible', $redacted['safe']);
    }

    public function test_it_redacts_ipg_and_refund_values(): void
    {
        $payload = [
            'api_key' => 'fake-ipg-api-key',
            'valid_card_number' => 'fake-card',
            'mobile_number' => 'fake-mobile',
            'cardNumber' => 'fake-card',
            'CID' => 'fake-cid',
            'cid' => 'fake-cid',
            'transId' => 'fake-trans-id',
            'refnumber' => 'fake-refnumber',
            'trackingCode' => 'fake-track-id',
            'payment_token' => 'fake-payment-token',
            'safe' => 'visible',
        ];

        $redacted = SensitiveDataRedactor::redact($payload);

        $this->assertSame('[redacted]', $redacted['api_key']);
        $this->assertSame('[redacted]', $redacted['valid_card_number']);
        $this->assertSame('[redacted]', $redacted['mobile_number']);
        $this->assertSame('[redacted]', $redacted['cardNumber']);
        $this->assertSame('[redacted]', $redacted['CID']);
        $this->assertSame('[redacted]', $redacted['cid']);
        $this->assertSame('[redacted]', $redacted['transId']);
        $this->assertSame('[redacted]', $redacted['refnumber']);
        $this->assertSame('[redacted]', $redacted['trackingCode']);
        $this->assertSame('[redacted]', $redacted['payment_token']);
        $this->assertSame('visible', $redacted['safe']);
    }
}
