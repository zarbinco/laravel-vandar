<?php

declare(strict_types=1);

namespace Zarbinco\LaravelVandar\Tests\Feature;

use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Http\Request as LaravelRequest;
use Zarbinco\LaravelVandar\Exceptions\VandarIpgCallbackException;
use Zarbinco\LaravelVandar\Facades\Vandar;
use Zarbinco\LaravelVandar\Tests\TestCase;

final class IpgCallbackSafetyTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('vandar.ipg.api_key', 'fake-ipg-api-key');
    }

    public function test_ok_callback_status_with_failed_verify_response_is_not_verified(): void
    {
        Vandar::fake([
            'ipg.verify' => [
                'status' => 400,
                'body' => [
                    'status' => 0,
                    'message' => 'Invalid token',
                ],
            ],
        ]);

        $result = Vandar::ipg()->verifyCallback($this->okCallback());

        $this->assertTrue($result->callbackHasOkStatus());
        $this->assertTrue($result->failed());
        $this->assertFalse($result->verified());
        $this->assertSame(400, $result->response()->status());
        $this->assertSame('Invalid token', $result->response()->message());
        Vandar::assertSent('ipg.verify', fn (HttpRequest $request): bool => $request['api_key'] === 'fake-ipg-api-key'
            && $request['token'] === 'fake-payment-token');
    }

    public function test_ok_callback_status_with_non_verified_verify_payload_is_not_verified(): void
    {
        Vandar::fake([
            'ipg.verify' => [
                'status' => 200,
                'body' => [
                    'status' => 'pending',
                    'transactionId' => 'fake-transaction-id',
                ],
            ],
        ]);

        $result = Vandar::ipg()->verifyCallback($this->okCallback());

        $this->assertTrue($result->callbackHasOkStatus());
        $this->assertTrue($result->successful());
        $this->assertFalse($result->verified());
        $this->assertSame('fake-transaction-id', $result->transactionId());
    }

    public function test_verify_callback_success_exposes_token_transaction_amount_and_factor_number(): void
    {
        Vandar::fake([
            'ipg.verify' => [
                'status' => 200,
                'body' => $this->successfulVerifyPayload(),
            ],
        ]);

        $result = Vandar::ipg()->verifyCallback($this->okCallback());

        $this->assertTrue($result->verified());
        $this->assertSame('fake-payment-token', $result->token());
        $this->assertSame('fake-transaction-id', $result->transactionId());
        $this->assertSame('fake-factor-number', $result->factorNumber());
        $this->assertSame(100000, $result->amount());
    }

    public function test_sdk_exposes_amount_mismatch_risk_without_deciding_application_state(): void
    {
        $expectedApplicationAmount = 100000;

        Vandar::fake([
            'ipg.verify' => [
                'status' => 200,
                'body' => $this->successfulVerifyPayload([
                    'amount' => 99000,
                ]),
            ],
        ]);

        $result = Vandar::ipg()->verifyCallback($this->okCallback());

        $this->assertTrue($result->verified());
        $this->assertSame(99000, $result->amount());
        $this->assertNotSame($expectedApplicationAmount, $result->amount());
    }

    public function test_sdk_exposes_factor_number_mismatch_risk_without_deciding_application_state(): void
    {
        $expectedApplicationFactorNumber = 'expected-factor-number';

        Vandar::fake([
            'ipg.verify' => [
                'status' => 200,
                'body' => $this->successfulVerifyPayload([
                    'factorNumber' => 'different-factor-number',
                ]),
            ],
        ]);

        $result = Vandar::ipg()->verifyCallback($this->okCallback());

        $this->assertTrue($result->verified());
        $this->assertSame('different-factor-number', $result->factorNumber());
        $this->assertNotSame($expectedApplicationFactorNumber, $result->factorNumber());
    }

    public function test_missing_token_in_request_throws_before_verify_is_called(): void
    {
        Vandar::fake([
            'ipg.verify' => [
                'status' => 200,
                'body' => $this->successfulVerifyPayload(),
            ],
        ]);

        $request = LaravelRequest::create('/payments/callback', 'POST', [
            'payment_status' => 'OK',
        ]);

        try {
            Vandar::ipg()->verifyCallback($request);
        } catch (VandarIpgCallbackException $exception) {
            $this->assertSame('Vandar IPG callback token is missing.', $exception->getMessage());
            Vandar::assertNotSent('ipg.verify');

            return;
        }

        $this->fail('Expected missing callback token exception was not thrown.');
    }

    public function test_unknown_token_returns_failed_verify_result_without_throwing(): void
    {
        Vandar::fake([
            'ipg.verify' => [
                'status' => 404,
                'body' => [
                    'message' => 'Payment token not found',
                ],
            ],
        ]);

        $result = Vandar::ipg()->verifyCallback([
            'token' => 'unknown-payment-token',
            'payment_status' => 'OK',
        ]);

        $this->assertSame('unknown-payment-token', $result->token());
        $this->assertTrue($result->failed());
        $this->assertFalse($result->verified());
        $this->assertSame('Payment token not found', $result->response()->message());
    }

    public function test_duplicate_callbacks_are_not_deduplicated_by_the_sdk(): void
    {
        Vandar::fake([
            'ipg.verify' => [
                'status' => 200,
                'body' => $this->successfulVerifyPayload(),
            ],
        ]);

        $first = Vandar::ipg()->verifyCallback($this->okCallback());
        $second = Vandar::ipg()->verifyCallback($this->okCallback());

        $this->assertTrue($first->verified());
        $this->assertTrue($second->verified());
        Vandar::assertSentCount('ipg.verify', 2);
    }

    public function test_deprecated_callback_succeeded_helper_only_checks_status_and_does_not_verify(): void
    {
        Vandar::fake([
            'ipg.verify' => [
                'status' => 200,
                'body' => $this->successfulVerifyPayload(),
            ],
        ]);

        $this->assertTrue(Vandar::ipg()->callbackSucceeded($this->okCallback()));
        Vandar::assertNotSent('ipg.verify');
    }

    public function test_malformed_verify_response_is_returned_as_unverified_response(): void
    {
        Vandar::fake([
            'ipg.verify' => [
                'status' => 200,
                'body' => '{"status":"OK",',
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
            ],
        ]);

        $result = Vandar::ipg()->verifyCallback($this->okCallback());

        $this->assertTrue($result->successful());
        $this->assertFalse($result->verified());
        $this->assertTrue($result->response()->jsonParseFailed());
        $this->assertSame('{"status":"OK",', $result->response()->body());
    }

    public function test_usage_docs_keep_callback_boundary_and_duplicate_handling_visible(): void
    {
        $usage = (string) file_get_contents(dirname(__DIR__, 2).DIRECTORY_SEPARATOR.'docs'.DIRECTORY_SEPARATOR.'usage.md');

        $this->assertStringContainsString('Safe callback controller skeleton', $usage);
        $this->assertStringContainsString('verify before marking anything paid', $usage);
        $this->assertStringContainsString('amount, factorNumber/order id, token, and transaction id', $usage);
        $this->assertStringContainsString('Duplicate callbacks must be handled idempotently', $usage);
        $this->assertStringContainsString('DB transaction belongs to your application', $usage);
    }

    /**
     * @return array<string, string>
     */
    private function okCallback(): array
    {
        return [
            'token' => 'fake-payment-token',
            'payment_status' => 'OK',
        ];
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function successfulVerifyPayload(array $overrides = []): array
    {
        return array_merge([
            'status' => 'OK',
            'transactionId' => 'fake-transaction-id',
            'factorNumber' => 'fake-factor-number',
            'amount' => 100000,
        ], $overrides);
    }
}
