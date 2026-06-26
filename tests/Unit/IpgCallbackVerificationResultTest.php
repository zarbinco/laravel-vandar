<?php

declare(strict_types=1);

namespace Zarbinco\LaravelVandar\Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Zarbinco\LaravelVandar\DTO\IpgCallbackVerificationResult;
use Zarbinco\LaravelVandar\DTO\VandarResponse;

final class IpgCallbackVerificationResultTest extends TestCase
{
    public function test_successful_only_follows_http_success(): void
    {
        $successful = new IpgCallbackVerificationResult(
            'fake-payment-token',
            'OK',
            new VandarResponse(200),
        );

        $failed = new IpgCallbackVerificationResult(
            'fake-payment-token',
            'OK',
            new VandarResponse(500, ['status' => 'OK']),
        );

        $this->assertTrue($successful->successful());
        $this->assertFalse($successful->failed());
        $this->assertFalse($failed->successful());
        $this->assertTrue($failed->failed());
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    #[DataProvider('verifiedPayloadProvider')]
    public function test_verified_is_true_for_clear_successful_verify_payloads(array $payload): void
    {
        $result = new IpgCallbackVerificationResult(
            'fake-payment-token',
            'OK',
            new VandarResponse(200, $payload),
        );

        $this->assertTrue($result->verified());
    }

    /**
     * @return array<string, array{array<string, mixed>}>
     */
    public static function verifiedPayloadProvider(): array
    {
        return [
            'top-level status OK' => [['status' => 'OK']],
            'nested status success' => [['data' => ['status' => 'success']]],
            'top-level payment status paid' => [['payment_status' => 'paid']],
            'nested payment status verified' => [['data' => ['payment_status' => 'verified']]],
            'top-level verified boolean' => [['verified' => true]],
            'nested verified integer' => [['data' => ['verified' => 1]]],
            'top-level status string one' => [['status' => '1']],
        ];
    }

    public function test_verified_is_false_for_http_success_with_unknown_or_empty_payload(): void
    {
        $empty = new IpgCallbackVerificationResult(
            'fake-payment-token',
            'OK',
            new VandarResponse(200),
        );

        $unknown = new IpgCallbackVerificationResult(
            'fake-payment-token',
            'OK',
            new VandarResponse(200, ['data' => ['message' => 'Done']]),
        );

        $this->assertFalse($empty->verified());
        $this->assertFalse($unknown->verified());
    }

    public function test_verified_is_false_for_failed_http_status_even_with_success_payload(): void
    {
        $result = new IpgCallbackVerificationResult(
            'fake-payment-token',
            'OK',
            new VandarResponse(500, ['status' => 'OK', 'verified' => true]),
        );

        $this->assertFalse($result->verified());
    }

    public function test_accessors_extract_common_top_level_keys(): void
    {
        $result = new IpgCallbackVerificationResult(
            'fake-payment-token',
            'OK',
            new VandarResponse(200, [
                'status' => 'OK',
                'transactionId' => 'fake-transaction-id',
                'factorNumber' => 'fake-factor-number',
                'amount' => 100000,
                'cardHash' => 'fake-card-hash',
                'CID' => 'fake-cid',
            ]),
        );

        $this->assertSame('fake-transaction-id', $result->transactionId());
        $this->assertSame('fake-factor-number', $result->factorNumber());
        $this->assertSame(100000, $result->amount());
        $this->assertSame('fake-card-hash', $result->cardHash());
        $this->assertSame('fake-cid', $result->cid());
    }

    public function test_accessors_extract_common_data_keys(): void
    {
        $result = new IpgCallbackVerificationResult(
            'fake-payment-token',
            'OK',
            new VandarResponse(200, [
                'data' => [
                    'status' => 'OK',
                    'transaction_id' => 'fake-data-transaction-id',
                    'factor_number' => 'fake-data-factor-number',
                    'amount' => '100000',
                    'card_hash' => 'fake-data-card-hash',
                    'cid' => 'fake-data-cid',
                ],
            ]),
        );

        $this->assertSame('fake-data-transaction-id', $result->transactionId());
        $this->assertSame('fake-data-factor-number', $result->factorNumber());
        $this->assertSame('100000', $result->amount());
        $this->assertSame('fake-data-card-hash', $result->cardHash());
        $this->assertSame('fake-data-cid', $result->cid());
    }

    public function test_to_array_returns_callback_metadata_and_response(): void
    {
        $response = new VandarResponse(200, [
            'status' => 'OK',
            'transactionId' => 'fake-transaction-id',
        ]);
        $result = new IpgCallbackVerificationResult('fake-payment-token', 'OK', $response);

        $this->assertSame([
            'token' => 'fake-payment-token',
            'callback_status' => 'OK',
            'callback_has_ok_status' => true,
            'successful' => true,
            'verified' => true,
            'transaction_id' => 'fake-transaction-id',
            'factor_number' => null,
            'amount' => null,
            'card_hash' => null,
            'cid' => null,
            'response' => $response->toArray(),
        ], $result->toArray());
    }
}
