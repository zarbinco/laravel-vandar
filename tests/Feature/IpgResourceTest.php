<?php

declare(strict_types=1);

namespace Zarbinco\LaravelVandar\Tests\Feature;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Zarbinco\LaravelVandar\DTO\VandarResponse;
use Zarbinco\LaravelVandar\Exceptions\VandarIpgApiKeyNotConfiguredException;
use Zarbinco\LaravelVandar\Facades\Vandar;
use Zarbinco\LaravelVandar\Tests\TestCase;

final class IpgResourceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('vandar.ipg.api_key', 'fake-ipg-api-key');
        config()->set('vandar.tokens.access_token', 'fake-access-token');
    }

    public function test_send_posts_to_ipg_send_endpoint(): void
    {
        Http::fake(['https://ipg.vandar.io/*' => Http::response(['token' => 'fake-payment-token'], 200)]);

        $response = Vandar::ipg()->send($this->paymentPayload());

        $this->assertInstanceOf(VandarResponse::class, $response);
        Http::assertSent(fn (Request $request): bool => $request->method() === 'POST'
            && $request->url() === 'https://ipg.vandar.io/api/v4/send'
            && $request['amount'] === 100000
            && $request['factorNumber'] === 'fake-factor-number');
    }

    public function test_send_attaches_api_key_from_config_when_payload_lacks_api_key(): void
    {
        Http::fake(['https://ipg.vandar.io/*' => Http::response(['token' => 'fake-payment-token'], 200)]);

        Vandar::ipg()->send($this->paymentPayload());

        Http::assertSent(fn (Request $request): bool => $request['api_key'] === 'fake-ipg-api-key');
    }

    public function test_send_does_not_override_payload_api_key(): void
    {
        Http::fake(['https://ipg.vandar.io/*' => Http::response(['token' => 'fake-payment-token'], 200)]);

        Vandar::ipg()->send($this->paymentPayload([
            'api_key' => 'fake-payload-ipg-api-key',
        ]));

        Http::assertSent(fn (Request $request): bool => $request['api_key'] === 'fake-payload-ipg-api-key');
    }

    public function test_send_attaches_callback_url_from_config_when_payload_lacks_callback_url(): void
    {
        config()->set('vandar.ipg.callback_url', 'fake-callback-url');
        Http::fake(['https://ipg.vandar.io/*' => Http::response(['token' => 'fake-payment-token'], 200)]);

        Vandar::ipg()->send([
            'amount' => 100000,
            'factorNumber' => 'fake-factor-number',
        ]);

        Http::assertSent(fn (Request $request): bool => $request['callback_url'] === 'fake-callback-url');
    }

    public function test_send_does_not_override_payload_callback_url(): void
    {
        config()->set('vandar.ipg.callback_url', 'fake-config-callback-url');
        Http::fake(['https://ipg.vandar.io/*' => Http::response(['token' => 'fake-payment-token'], 200)]);

        Vandar::ipg()->send($this->paymentPayload([
            'callback_url' => 'fake-payload-callback-url',
        ]));

        Http::assertSent(fn (Request $request): bool => $request['callback_url'] === 'fake-payload-callback-url');
    }

    public function test_send_does_not_attach_authorization_header(): void
    {
        Http::fake(['https://ipg.vandar.io/*' => Http::response(['token' => 'fake-payment-token'], 200)]);

        Vandar::ipg()->send($this->paymentPayload());

        Http::assertSent(fn (Request $request): bool => ! $request->hasHeader('Authorization'));
    }

    public function test_send_returns_failed_response_without_throwing_automatically(): void
    {
        Http::fake(['https://ipg.vandar.io/*' => Http::response(['message' => 'Failed'], 422)]);

        $response = Vandar::ipg()->send($this->paymentPayload());

        $this->assertInstanceOf(VandarResponse::class, $response);
        $this->assertSame(422, $response->status());
    }

    public function test_missing_api_key_throws(): void
    {
        config()->set('vandar.ipg.api_key', null);

        $this->expectException(VandarIpgApiKeyNotConfiguredException::class);

        Vandar::ipg()->send($this->paymentPayload());
    }

    public function test_redirect_url_returns_encoded_gateway_url(): void
    {
        $this->assertSame(
            'https://ipg.vandar.io/v4/fake%20payment%2Ftoken',
            Vandar::ipg()->redirectUrl('fake payment/token'),
        );
    }

    public function test_gateway_url_aliases_redirect_url(): void
    {
        $this->assertSame(
            Vandar::ipg()->redirectUrl('fake-payment-token'),
            Vandar::ipg()->gatewayUrl('fake-payment-token'),
        );
    }

    public function test_transaction_posts_token_and_api_key(): void
    {
        Http::fake(['https://ipg.vandar.io/*' => Http::response(['data' => []], 200)]);

        Vandar::ipg()->transaction('fake-payment-token');

        Http::assertSent(fn (Request $request): bool => $request->method() === 'POST'
            && $request->url() === 'https://ipg.vandar.io/api/v4/transaction'
            && $request['api_key'] === 'fake-ipg-api-key'
            && $request['token'] === 'fake-payment-token');
    }

    public function test_transaction_does_not_attach_authorization_header(): void
    {
        Http::fake(['https://ipg.vandar.io/*' => Http::response(['data' => []], 200)]);

        Vandar::ipg()->transaction('fake-payment-token');

        Http::assertSent(fn (Request $request): bool => ! $request->hasHeader('Authorization'));
    }

    public function test_verify_posts_token_and_api_key(): void
    {
        Http::fake(['https://ipg.vandar.io/*' => Http::response(['data' => []], 200)]);

        Vandar::ipg()->verify('fake-payment-token');

        Http::assertSent(fn (Request $request): bool => $request->method() === 'POST'
            && $request->url() === 'https://ipg.vandar.io/api/v4/verify'
            && $request['api_key'] === 'fake-ipg-api-key'
            && $request['token'] === 'fake-payment-token');
    }

    public function test_verify_does_not_attach_authorization_header(): void
    {
        Http::fake(['https://ipg.vandar.io/*' => Http::response(['data' => []], 200)]);

        Vandar::ipg()->verify('fake-payment-token');

        Http::assertSent(fn (Request $request): bool => ! $request->hasHeader('Authorization'));
    }

    public function test_verify_returns_failed_response_without_throwing_automatically(): void
    {
        Http::fake(['https://ipg.vandar.io/*' => Http::response(['message' => 'Failed'], 400)]);

        $response = Vandar::ipg()->verify('fake-payment-token');

        $this->assertInstanceOf(VandarResponse::class, $response);
        $this->assertSame(400, $response->status());
    }

    public function test_callback_succeeded_returns_true_for_ok_status(): void
    {
        $this->assertTrue(Vandar::ipg()->callbackSucceeded(['payment_status' => 'OK']));
    }

    public function test_callback_succeeded_returns_false_for_failed_status(): void
    {
        $this->assertFalse(Vandar::ipg()->callbackSucceeded(['payment_status' => 'FAILED']));
    }

    public function test_callback_token_extracts_token_from_array(): void
    {
        $this->assertSame('fake-payment-token', Vandar::ipg()->callbackToken(['token' => 'fake-payment-token']));
    }

    public function test_callback_status_extracts_payment_status_from_array(): void
    {
        $this->assertSame('OK', Vandar::ipg()->callbackStatus(['payment_status' => 'OK']));
    }

    public function test_money_moving_ipg_calls_are_not_retried_automatically(): void
    {
        config()->set('vandar.http.retry.enabled', true);
        config()->set('vandar.http.retry.times', 3);

        Http::fake(['https://ipg.vandar.io/*' => Http::response(['message' => 'Failed'], 500)]);

        Vandar::ipg()->send($this->paymentPayload());
        Vandar::ipg()->transaction('fake-payment-token');
        Vandar::ipg()->verify('fake-payment-token');

        Http::assertSentCount(3);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function paymentPayload(array $overrides = []): array
    {
        return array_merge([
            'amount' => 100000,
            'callback_url' => 'fake-callback-url',
            'factorNumber' => 'fake-factor-number',
            'description' => 'Fake payment description',
        ], $overrides);
    }
}
