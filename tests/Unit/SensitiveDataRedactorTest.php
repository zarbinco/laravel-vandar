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
            'quantity' => 10000,
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
            'nationalCode' => 'fake-national-code',
            'individual_national_code' => 'fake-individual-national-code',
            'legal_national_code' => 'fake-legal-national-code',
            'fida_code' => 'fake-fida-code',
            'birthday' => 'fake-birthday',
            'birth_date' => 'fake-birth-date',
            'birthDate' => 'fake-birth-date',
            'birthCertificateNumber' => 'fake-birth-certificate-number',
            'identity_number' => 'fake-identity-number',
            'identityNumber' => 'fake-identity-number',
            'postal_code' => 'fake-postal-code',
            'card' => 'fake-card',
            'iban' => 'fake-iban',
            'image' => 'fake-image',
            'images' => ['fake-image'],
            'safe' => 'visible',
        ];

        $redacted = SensitiveDataRedactor::redact($payload);

        $this->assertSame('[redacted]', $redacted['national_code']);
        $this->assertSame('[redacted]', $redacted['nationalCode']);
        $this->assertSame('[redacted]', $redacted['individual_national_code']);
        $this->assertSame('[redacted]', $redacted['legal_national_code']);
        $this->assertSame('[redacted]', $redacted['fida_code']);
        $this->assertSame('[redacted]', $redacted['birthday']);
        $this->assertSame('[redacted]', $redacted['birth_date']);
        $this->assertSame('[redacted]', $redacted['birthDate']);
        $this->assertSame('[redacted]', $redacted['birthCertificateNumber']);
        $this->assertSame('[redacted]', $redacted['identity_number']);
        $this->assertSame('[redacted]', $redacted['identityNumber']);
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

    public function test_it_redacts_settlement_batch_and_avand_values(): void
    {
        $payload = [
            'track_id' => 'fake-track-id',
            'tracking_code' => 'fake-tracking-code',
            'trackingCode' => 'fake-tracking-code',
            'trackId' => 'fake-track-id',
            'authorization_id' => 'fake-authorization-id',
            'authorizationId' => 'fake-authorization-id',
            'withdrawal_id' => 'fake-withdrawal-id',
            'withdrawalId' => 'fake-withdrawal-id',
            'refund_id' => 'fake-refund-id',
            'refundId' => 'fake-refund-id',
            'mandate_id' => 'fake-mandate-id',
            'mandateId' => 'fake-mandate-id',
            'subscription_id' => 'fake-subscription-id',
            'subscriptionId' => 'fake-subscription-id',
            'settlement_id' => 'fake-settlement-id',
            'settlement_track_id' => 'fake-track-id',
            'batch_id' => 'fake-batch-id',
            'batchId' => 'fake-batch-id',
            'queued_id' => 'fake-queued-id',
            'queuedId' => 'fake-queued-id',
            'transactionId' => 'fake-transaction-id',
            'amount' => 100000,
            'wage' => 1000,
            'fee' => 500,
            'destination_iban' => 'fake-iban',
            'source_iban' => 'fake-iban',
            'bank_account' => 'fake-account-number',
            'bankAccount' => 'fake-account-number',
            'account' => 'fake-account-number',
            'accountNumber' => 'fake-account-number',
            'transfer_id' => 'fake-transfer-id',
            'deposit_id' => 'fake-deposit-id',
            'suspicious_payment_id' => 'fake-suspicious-payment-id',
            'payment_identifier' => 'fake-payment-identifier',
            'cash_in_code' => 'fake-cash-in-code',
            'balance' => 100000,
            'last_balance' => 100000,
            'statement' => 'fake-statement',
            'realtime_statement' => 'fake-realtime-statement',
            'label' => 'fake-label',
            'reference' => 'fake-reference',
            'safe' => 'visible',
        ];

        $redacted = SensitiveDataRedactor::redact($payload);

        $this->assertSame('[redacted]', $redacted['track_id']);
        $this->assertSame('[redacted]', $redacted['tracking_code']);
        $this->assertSame('[redacted]', $redacted['trackingCode']);
        $this->assertSame('[redacted]', $redacted['trackId']);
        $this->assertSame('[redacted]', $redacted['authorization_id']);
        $this->assertSame('[redacted]', $redacted['authorizationId']);
        $this->assertSame('[redacted]', $redacted['withdrawal_id']);
        $this->assertSame('[redacted]', $redacted['withdrawalId']);
        $this->assertSame('[redacted]', $redacted['refund_id']);
        $this->assertSame('[redacted]', $redacted['refundId']);
        $this->assertSame('[redacted]', $redacted['mandate_id']);
        $this->assertSame('[redacted]', $redacted['mandateId']);
        $this->assertSame('[redacted]', $redacted['subscription_id']);
        $this->assertSame('[redacted]', $redacted['subscriptionId']);
        $this->assertSame('[redacted]', $redacted['settlement_id']);
        $this->assertSame('[redacted]', $redacted['settlement_track_id']);
        $this->assertSame('[redacted]', $redacted['batch_id']);
        $this->assertSame('[redacted]', $redacted['batchId']);
        $this->assertSame('[redacted]', $redacted['queued_id']);
        $this->assertSame('[redacted]', $redacted['queuedId']);
        $this->assertSame('[redacted]', $redacted['transactionId']);
        $this->assertSame('[redacted]', $redacted['amount']);
        $this->assertSame('[redacted]', $redacted['wage']);
        $this->assertSame('[redacted]', $redacted['fee']);
        $this->assertSame('[redacted]', $redacted['destination_iban']);
        $this->assertSame('[redacted]', $redacted['source_iban']);
        $this->assertSame('[redacted]', $redacted['bank_account']);
        $this->assertSame('[redacted]', $redacted['bankAccount']);
        $this->assertSame('[redacted]', $redacted['account']);
        $this->assertSame('[redacted]', $redacted['accountNumber']);
        $this->assertSame('[redacted]', $redacted['transfer_id']);
        $this->assertSame('[redacted]', $redacted['deposit_id']);
        $this->assertSame('[redacted]', $redacted['suspicious_payment_id']);
        $this->assertSame('[redacted]', $redacted['payment_identifier']);
        $this->assertSame('[redacted]', $redacted['cash_in_code']);
        $this->assertSame('[redacted]', $redacted['balance']);
        $this->assertSame('[redacted]', $redacted['last_balance']);
        $this->assertSame('[redacted]', $redacted['statement']);
        $this->assertSame('[redacted]', $redacted['realtime_statement']);
        $this->assertSame('[redacted]', $redacted['label']);
        $this->assertSame('[redacted]', $redacted['reference']);
        $this->assertSame('visible', $redacted['safe']);
    }

    public function test_it_redacts_sensitive_json_like_body_text(): void
    {
        $body = '{"token":"fake-token","apiKey":"fake-api-key","nationalCode":"fake-national-code","nested":{"email":"fake@example.test","pan":"fake-pan","identityNumber":"fake-identity-number"}}';

        $redacted = SensitiveDataRedactor::redactText($body);

        $this->assertSame(
            '{"token":"[REDACTED]","apiKey":"[REDACTED]","nationalCode":"[REDACTED]","nested":{"email":"[REDACTED]","pan":"[REDACTED]","identityNumber":"[REDACTED]"}}',
            $redacted,
        );
    }

    public function test_it_redacts_sensitive_plain_text_body_values(): void
    {
        $body = "token=fake-token&iban=fake-iban\nAuthorization: Bearer fake-authorization-token\nphone=fake-phone";

        $redacted = SensitiveDataRedactor::redactText($body);

        $this->assertSame(
            "token=[REDACTED]&iban=[REDACTED]\nAuthorization:[REDACTED]\nphone=[REDACTED]",
            $redacted,
        );
    }

    public function test_body_text_redaction_is_deterministic(): void
    {
        $body = 'mobile=fake-mobile&cid=fake-cid';

        $this->assertSame(
            SensitiveDataRedactor::redactText($body),
            SensitiveDataRedactor::redactText($body),
        );
    }

    public function test_it_redacts_token_shaped_words_in_body_text(): void
    {
        $body = 'Denied fake-access-token and fake-refresh-token.';

        $redacted = SensitiveDataRedactor::redactText($body);

        $this->assertStringNotContainsString('fake-access-token', $redacted);
        $this->assertStringNotContainsString('fake-refresh-token', $redacted);
        $this->assertStringContainsString('[REDACTED]', $redacted);
    }
}
