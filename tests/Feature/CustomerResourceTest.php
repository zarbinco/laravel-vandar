<?php

declare(strict_types=1);

namespace Zarbinco\LaravelVandar\Tests\Feature;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Zarbinco\LaravelVandar\DTO\VandarResponse;
use Zarbinco\LaravelVandar\Facades\Vandar;
use Zarbinco\LaravelVandar\Tests\TestCase;

final class CustomerResourceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('vandar.business', 'test-business');
        config()->set('vandar.tokens.access_token', 'fake-access-token');
        config()->set('vandar.tokens.refresh_token', 'fake-refresh-token');
    }

    public function test_list_calls_customers_endpoint_with_query(): void
    {
        Http::fake(['https://api.vandar.io/*' => Http::response(['data' => []], 200)]);

        $response = Vandar::customers()->list(['page' => 1]);

        $this->assertInstanceOf(VandarResponse::class, $response);
        Http::assertSent(fn (Request $request): bool => $request->method() === 'GET'
            && str_starts_with($request->url(), 'https://api.vandar.io/v2/business/test-business/customers')
            && str_contains($request->url(), 'page=1'));
    }

    public function test_create_calls_customers_endpoint_with_payload(): void
    {
        Http::fake(['https://api.vandar.io/*' => Http::response(['id' => 'fake-customer-id'], 201)]);

        Vandar::customers()->create(['first_name' => 'Test']);

        Http::assertSent(fn (Request $request): bool => $request->method() === 'POST'
            && $request->url() === 'https://api.vandar.io/v2/business/test-business/customers'
            && $request['first_name'] === 'Test');
    }

    public function test_create_individual_adds_type_when_missing(): void
    {
        Http::fake(['https://api.vandar.io/*' => Http::response(['ok' => true], 201)]);

        Vandar::customers()->createIndividual(['first_name' => 'Test']);

        Http::assertSent(fn (Request $request): bool => $request['type'] === 'INDIVIDUAL');
    }

    public function test_create_legal_adds_type_when_missing(): void
    {
        Http::fake(['https://api.vandar.io/*' => Http::response(['ok' => true], 201)]);

        Vandar::customers()->createLegal(['legal_name' => 'Test Company']);

        Http::assertSent(fn (Request $request): bool => $request['type'] === 'LEGAL');
    }

    public function test_create_individual_does_not_override_existing_type(): void
    {
        Http::fake(['https://api.vandar.io/*' => Http::response(['ok' => true], 201)]);

        Vandar::customers()->createIndividual(['type' => 'CUSTOM', 'first_name' => 'Test']);

        Http::assertSent(fn (Request $request): bool => $request['type'] === 'CUSTOM');
    }

    public function test_update_calls_customer_endpoint(): void
    {
        Http::fake(['https://api.vandar.io/*' => Http::response(['ok' => true], 200)]);

        Vandar::customers()->update('fake-customer-id', ['first_name' => 'Updated']);

        Http::assertSent(fn (Request $request): bool => $request->method() === 'PUT'
            && $request->url() === 'https://api.vandar.io/v2/business/test-business/customers/fake-customer-id'
            && $request['first_name'] === 'Updated');
    }

    public function test_update_individual_adds_type_when_missing(): void
    {
        Http::fake(['https://api.vandar.io/*' => Http::response(['ok' => true], 200)]);

        Vandar::customers()->updateIndividual('fake-customer-id', ['first_name' => 'Updated']);

        Http::assertSent(fn (Request $request): bool => $request['type'] === 'INDIVIDUAL');
    }

    public function test_update_legal_adds_type_when_missing(): void
    {
        Http::fake(['https://api.vandar.io/*' => Http::response(['ok' => true], 200)]);

        Vandar::customers()->updateLegal('fake-customer-id', ['legal_name' => 'Updated Company']);

        Http::assertSent(fn (Request $request): bool => $request['type'] === 'LEGAL');
    }

    public function test_delete_calls_customer_endpoint(): void
    {
        Http::fake(['https://api.vandar.io/*' => Http::response([], 204)]);

        Vandar::customers()->delete('fake-customer-id');

        Http::assertSent(fn (Request $request): bool => $request->method() === 'DELETE'
            && $request->url() === 'https://api.vandar.io/v2/business/test-business/customers/fake-customer-id');
    }

    public function test_find_and_show_call_customer_endpoint(): void
    {
        Http::fake(['https://api.vandar.io/*' => Http::response(['ok' => true], 200)]);

        Vandar::customers()->find('fake-customer-id');
        Vandar::customers()->show('fake-customer-id');

        Http::assertSentCount(2);
        Http::assertSent(fn (Request $request): bool => $request->method() === 'GET'
            && $request->url() === 'https://api.vandar.io/v2/business/test-business/customers/fake-customer-id');
    }

    public function test_wallet_balance_calls_customer_wallet_endpoint(): void
    {
        Http::fake(['https://api.vandar.io/*' => Http::response(['balance' => 1000], 200)]);

        Vandar::customers()->walletBalance('fake-customer-id');

        Http::assertSent(fn (Request $request): bool => $request->method() === 'GET'
            && $request->url() === 'https://api.vandar.io/v2/business/test-business/customers/fake-customer-id/wallet');
    }

    public function test_wallet_deposit_calls_customer_wallet_deposit_endpoint(): void
    {
        Http::fake(['https://api.vandar.io/*' => Http::response(['ok' => true], 200)]);

        Vandar::customers()->walletDeposit('fake-customer-id', ['amount' => 1000, 'track_id' => 'fake-track-id']);

        Http::assertSent(fn (Request $request): bool => $request->method() === 'POST'
            && $request->url() === 'https://api.vandar.io/v2/business/test-business/customers/fake-customer-id/wallet/deposit'
            && $request['track_id'] === 'fake-track-id');
    }

    public function test_wallet_withdraw_calls_customer_wallet_withdraw_endpoint(): void
    {
        Http::fake(['https://api.vandar.io/*' => Http::response(['ok' => true], 200)]);

        Vandar::customers()->walletWithdraw('fake-customer-id', ['amount' => 500, 'track_id' => 'fake-track-id']);

        Http::assertSent(fn (Request $request): bool => $request->method() === 'POST'
            && $request->url() === 'https://api.vandar.io/v2/business/test-business/customers/fake-customer-id/wallet/withdraw'
            && $request['amount'] === 500);
    }

    public function test_transactions_calls_customer_transactions_endpoint_with_post_payload(): void
    {
        Http::fake(['https://api.vandar.io/*' => Http::response(['data' => []], 200)]);

        Vandar::customers()->transactions('fake-customer-id', ['page' => 1]);

        Http::assertSent(fn (Request $request): bool => $request->method() === 'POST'
            && $request->url() === 'https://api.vandar.io/v2/business/test-business/customers/fake-customer-id/transactions'
            && $request['page'] === 1);
    }

    public function test_authentication_kyc_posts_customer_scoped_json_payload(): void
    {
        Http::fake(['https://api.vandar.io/*' => Http::response(['ok' => true], 200)]);

        $response = Vandar::customers()->authenticationKyc('fake-customer-id', [
            'national_code' => 'fake-national-code',
            'birth_date' => 'fake-birth-date',
        ]);

        $this->assertInstanceOf(VandarResponse::class, $response);
        Http::assertSentCount(1);
        Http::assertSent(fn (Request $request): bool => $request->method() === 'POST'
            && $request->url() === 'https://api.vandar.io/v3/business/test-business/customers/fake-customer-id/authentication/kyc'
            && $request->hasHeader('Authorization', 'Bearer fake-access-token')
            && $request->hasHeader('Content-Type', 'application/json')
            && $request['national_code'] === 'fake-national-code'
            && $request['birth_date'] === 'fake-birth-date');
    }

    public function test_authentication_shahkar_posts_customer_scoped_json_payload(): void
    {
        Http::fake(['https://api.vandar.io/*' => Http::response(['ok' => true], 200)]);

        Vandar::customers()->authenticationShahkar('fake-customer-id', [
            'mobile' => 'fake-mobile',
            'national_code' => 'fake-national-code',
        ]);

        Http::assertSentCount(1);
        Http::assertSent(fn (Request $request): bool => $request->method() === 'POST'
            && $request->url() === 'https://api.vandar.io/v3/business/test-business/customers/fake-customer-id/authentication/shahkar'
            && $request->hasHeader('Authorization', 'Bearer fake-access-token')
            && $request->hasHeader('Content-Type', 'application/json')
            && $request['mobile'] === 'fake-mobile'
            && $request['national_code'] === 'fake-national-code');
    }

    public function test_customer_scoped_v3_paths_encode_business_and_customer_segments(): void
    {
        Http::fake(['https://api.vandar.io/*' => Http::response(['ok' => true], 200)]);

        Vandar::customers()->authenticationKyc('customer/id with space', [
            'national_code' => 'fake-national-code',
        ], 'business/id with space');

        Http::assertSent(fn (Request $request): bool => $request->url() === 'https://api.vandar.io/v3/business/business%2Fid%20with%20space/customers/customer%2Fid%20with%20space/authentication/kyc');
    }

    public function test_cash_in_code_gets_customer_scoped_code(): void
    {
        Http::fake(['https://api.vandar.io/*' => Http::response(['code' => 'fake-code'], 200)]);

        $response = Vandar::customers()->cashInCode('fake-customer-id');

        $this->assertInstanceOf(VandarResponse::class, $response);
        Http::assertSentCount(1);
        Http::assertSent(fn (Request $request): bool => $request->method() === 'GET'
            && $request->url() === 'https://api.vandar.io/v3/business/test-business/customers/fake-customer-id/cash-in-code'
            && $request->hasHeader('Authorization', 'Bearer fake-access-token'));
    }

    public function test_delete_cash_in_code_destroys_customer_scoped_code(): void
    {
        Http::fake(['https://api.vandar.io/*' => Http::response([], 204)]);

        Vandar::customers()->deleteCashInCode('fake-customer-id');

        Http::assertSentCount(1);
        Http::assertSent(fn (Request $request): bool => $request->method() === 'DELETE'
            && $request->url() === 'https://api.vandar.io/v3/business/test-business/customers/fake-customer-id/cash-in-code/destroy'
            && $request->hasHeader('Authorization', 'Bearer fake-access-token'));
    }

    public function test_delete_cash_in_code_is_not_retried_automatically(): void
    {
        config()->set('vandar.rate_limit.max_retry_after_seconds', 0);

        Http::fake([
            'https://api.vandar.io/*' => Http::sequence()
                ->push(['message' => 'Too many requests'], 429, ['Retry-After' => '0'])
                ->push(['ok' => true], 200),
        ]);

        $response = Vandar::customers()->deleteCashInCode('fake-customer-id');

        $this->assertTrue($response->tooManyRequests());
        Http::assertSentCount(1);
    }

    public function test_all_alias_calls_list_endpoint(): void
    {
        Http::fake(['https://api.vandar.io/*' => Http::response(['data' => []], 200)]);

        Vandar::customers()->all(['page' => 1]);

        Http::assertSent(fn (Request $request): bool => $request->method() === 'GET'
            && str_starts_with($request->url(), 'https://api.vandar.io/v2/business/test-business/customers'));
    }

    public function test_failed_response_does_not_throw_automatically(): void
    {
        Http::fake(['https://api.vandar.io/*' => Http::response(['message' => 'Failed'], 422)]);

        $response = Vandar::customers()->create(['first_name' => 'Test']);

        $this->assertInstanceOf(VandarResponse::class, $response);
        $this->assertSame(422, $response->status());
    }

    public function test_logging_does_not_expose_fake_tokens(): void
    {
        config()->set('vandar.logging.enabled', true);
        Log::spy();

        Http::fake(['https://api.vandar.io/*' => Http::response(['ok' => true], 200)]);

        Vandar::customers()->list(['access_token' => 'fake-access-token', 'normal' => 'yes']);

        Log::shouldHaveReceived('debug')
            ->once()
            ->withArgs(function (string $message, array $context): bool {
                $encoded = json_encode($context);

                return $message === 'Vandar HTTP request'
                    && is_string($encoded)
                    && ! str_contains($encoded, 'fake-access-token')
                    && str_contains($encoded, '[redacted]')
                    && str_contains($encoded, 'normal')
                    && str_contains($encoded, 'yes');
            });
    }

    public function test_customer_authentication_logging_redacts_identity_payload_and_customer_path(): void
    {
        config()->set('vandar.logging.enabled', true);
        Log::spy();

        Http::fake(['https://api.vandar.io/*' => Http::response(['ok' => true], 200)]);

        Vandar::customers()->authenticationKyc('fake-customer-id', [
            'nationalCode' => 'fake-national-code',
            'birthDate' => 'fake-birth-date',
        ]);

        Log::shouldHaveReceived('debug')
            ->once()
            ->withArgs(function (string $message, array $context): bool {
                $encoded = json_encode($context);

                return $message === 'Vandar HTTP request'
                    && is_string($encoded)
                    && ($context['url'] ?? null) === 'https://api.vandar.io/v3/business/test-business/customers/[redacted]/authentication/kyc'
                    && ! str_contains($encoded, 'fake-customer-id')
                    && ! str_contains($encoded, 'fake-national-code')
                    && ! str_contains($encoded, 'fake-birth-date')
                    && str_contains($encoded, '[redacted]');
            });
    }
}
