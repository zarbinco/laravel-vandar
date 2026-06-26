<?php

declare(strict_types=1);

namespace Zarbinco\LaravelVandar\Tests\Feature;

use Illuminate\Support\Facades\Http;
use Zarbinco\LaravelVandar\Exceptions\VandarRequestException;
use Zarbinco\LaravelVandar\Http\VandarClient;
use Zarbinco\LaravelVandar\Tests\TestCase;

final class PendingVandarRequestResponseBodyTest extends TestCase
{
    public function test_valid_json_response_stores_parsed_json_and_raw_body(): void
    {
        $body = '{"data":{"ok":true},"token":"fake-payment-token"}';
        Http::fake([
            'https://api.vandar.io/*' => Http::response($body, 200, [
                'Content-Type' => 'application/json',
            ]),
        ]);

        $response = $this->app->make(VandarClient::class)->get('api', '/v2/manual', auth: false);

        $this->assertSame(['data' => ['ok' => true], 'token' => 'fake-payment-token'], $response->json());
        $this->assertSame($body, $response->body());
        $this->assertFalse($response->jsonParseFailed());
        $this->assertTrue($response->isJson());
    }

    public function test_empty_body_response_does_not_mark_json_parse_failed(): void
    {
        Http::fake([
            'https://api.vandar.io/*' => Http::response('', 204, [
                'Content-Type' => 'application/json',
            ]),
        ]);

        $response = $this->app->make(VandarClient::class)->get('api', '/v2/manual', auth: false);

        $this->assertSame([], $response->json());
        $this->assertSame('', $response->body());
        $this->assertFalse($response->hasBody());
        $this->assertFalse($response->jsonParseFailed());
    }

    public function test_html_text_response_stores_raw_body_and_does_not_crash(): void
    {
        $body = '<html>Unexpected upstream response</html>';
        Http::fake([
            'https://api.vandar.io/*' => Http::response($body, 502, [
                'Content-Type' => 'text/html',
            ]),
        ]);

        $response = $this->app->make(VandarClient::class)->get('api', '/v2/manual', auth: false);

        $this->assertSame([], $response->json());
        $this->assertSame($body, $response->body());
        $this->assertTrue($response->jsonParseFailed());
        $this->assertFalse($response->isJson());
    }

    public function test_invalid_json_with_json_content_type_marks_json_parse_failed(): void
    {
        Http::fake([
            'https://api.vandar.io/*' => Http::response('{"status":"OK",', 200, [
                'Content-Type' => 'application/json',
            ]),
        ]);

        $response = $this->app->make(VandarClient::class)->get('api', '/v2/manual', auth: false);

        $this->assertSame([], $response->json());
        $this->assertTrue($response->jsonParseFailed());
    }

    public function test_json_scalar_response_keeps_compatible_empty_json_array(): void
    {
        Http::fake([
            'https://api.vandar.io/*' => Http::response('"ok"', 200, [
                'Content-Type' => 'application/json',
            ]),
        ]);

        $response = $this->app->make(VandarClient::class)->get('api', '/v2/manual', auth: false);

        $this->assertSame([], $response->json());
        $this->assertSame('"ok"', $response->body());
        $this->assertTrue($response->jsonParseFailed());
    }

    public function test_malformed_json_response_can_be_inspected_safely(): void
    {
        $body = '{"token":"fake-secret-token","factorNumber":"fake-factor-number","settlement_id":"fake-settlement-id","customer_id":"fake-customer-id",';
        Http::fake([
            'https://api.vandar.io/*' => Http::response($body, 500, [
                'Content-Type' => 'application/json',
            ]),
        ]);

        $response = $this->app->make(VandarClient::class)->get('api', '/v2/manual', auth: false);

        $this->assertSame($body, $response->body());
        $this->assertTrue($response->jsonParseFailed());
        $this->assertStringContainsString('[REDACTED]', (string) $response->redactedBody());
        $this->assertStringNotContainsString('fake-secret-token', (string) $response->redactedBody());
        $this->assertStringNotContainsString('fake-factor-number', (string) $response->redactedBody());
        $this->assertStringNotContainsString('fake-settlement-id', (string) $response->redactedBody());
        $this->assertStringNotContainsString('fake-customer-id', (string) $response->redactedBody());
    }

    public function test_failed_response_body_values_are_redacted_in_exception_context(): void
    {
        Http::fake([
            'https://api.vandar.io/*' => Http::response(
                '{"message":"Failed","token":"fake-secret-token","card_number":"fake-card-number","factorNumber":"fake-factor-number","data":{"iban":"fake-iban","customer_id":"fake-customer-id"}}',
                422,
                ['Content-Type' => 'application/json'],
            ),
        ]);

        $response = $this->app->make(VandarClient::class)->post('api', '/v2/manual', ['field' => 'bad'], auth: false);

        try {
            $response->throw();
        } catch (VandarRequestException $exception) {
            $encodedContext = json_encode($exception->context());

            $this->assertIsString($encodedContext);
            $this->assertStringNotContainsString('fake-secret-token', $encodedContext);
            $this->assertStringNotContainsString('fake-card-number', $encodedContext);
            $this->assertStringNotContainsString('fake-factor-number', $encodedContext);
            $this->assertStringNotContainsString('fake-customer-id', $encodedContext);
            $this->assertStringNotContainsString('fake-iban', $encodedContext);
            $this->assertStringContainsString('redacted_body', $encodedContext);
            $this->assertStringContainsString('json_parse_failed', $encodedContext);
            $this->assertStringContainsString('[REDACTED]', $encodedContext);

            return;
        }

        $this->fail('Expected Vandar request exception was not thrown.');
    }
}
