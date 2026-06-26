<?php

declare(strict_types=1);

namespace Zarbinco\LaravelVandar\Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Zarbinco\LaravelVandar\DTO\VandarResponse;
use Zarbinco\LaravelVandar\Exceptions\VandarAuthenticationException;
use Zarbinco\LaravelVandar\Exceptions\VandarAuthorizationException;
use Zarbinco\LaravelVandar\Exceptions\VandarRateLimitException;
use Zarbinco\LaravelVandar\Exceptions\VandarRequestException;
use Zarbinco\LaravelVandar\Exceptions\VandarServerException;
use Zarbinco\LaravelVandar\Exceptions\VandarValidationException;

final class VandarResponseTest extends TestCase
{
    public function test_status_helpers_and_accessors_work(): void
    {
        $response = new VandarResponse(200, [
            'message' => 'Done',
            'data' => ['id' => 123],
            'track_id' => 'fake-track-id',
        ], ['X-Test' => ['yes']]);

        $this->assertTrue($response->successful());
        $this->assertFalse($response->failed());
        $this->assertSame(123, $response->data('id'));
        $this->assertSame('Done', $response->message());
        $this->assertSame('fake-track-id', $response->trackId());
        $this->assertSame('yes', $response->header('x-test'));
        $this->assertSame(200, $response->throw()->status());
    }

    public function test_errors_helper_uses_common_error_shapes(): void
    {
        $this->assertSame(['amount' => ['Invalid']], (new VandarResponse(422, [
            'errors' => ['amount' => ['Invalid']],
        ]))->errors());

        $this->assertSame(['error' => 'Invalid'], (new VandarResponse(400, [
            'error' => 'Invalid',
        ]))->errors());
    }

    public function test_value_helper_uses_dot_notation(): void
    {
        $response = new VandarResponse(200, [
            'data' => [
                'transaction' => [
                    'id' => 'fake-transaction-id',
                ],
            ],
        ]);

        $this->assertSame('fake-transaction-id', $response->value('data.transaction.id'));
        $this->assertSame('fallback', $response->value('data.missing', 'fallback'));
    }

    public function test_value_helper_uses_fallback_key_list(): void
    {
        $response = new VandarResponse(200, [
            'data' => [
                'payment_status' => 'OK',
            ],
        ]);

        $this->assertSame('OK', $response->value(['payment_status', 'data.payment_status']));
        $this->assertSame('fallback', $response->value(['missing', 'data.missing'], 'fallback'));
    }

    public function test_string_int_and_bool_helpers_convert_scalar_values(): void
    {
        $response = new VandarResponse(200, [
            'string_value' => 123,
            'int_value' => '100000',
            'bool_true' => 'true',
            'bool_false' => 0,
        ]);

        $this->assertSame('123', $response->string('string_value'));
        $this->assertSame(100000, $response->int('int_value'));
        $this->assertTrue($response->bool('bool_true'));
        $this->assertFalse($response->bool('bool_false'));
    }

    public function test_scalar_helpers_return_defaults_for_invalid_values(): void
    {
        $response = new VandarResponse(200, [
            'array_value' => ['nested' => true],
            'invalid_int' => 'not-a-number',
            'invalid_bool' => 'not-a-bool',
        ]);

        $this->assertSame('fallback', $response->scalar('array_value', 'fallback'));
        $this->assertSame('fallback', $response->string('array_value', 'fallback'));
        $this->assertSame(5, $response->int('invalid_int', 5));
        $this->assertTrue($response->bool('invalid_bool', true));
    }

    public function test_body_returns_raw_body(): void
    {
        $response = new VandarResponse(200, body: '{"token":"fake-token"}');

        $this->assertSame('{"token":"fake-token"}', $response->body());
    }

    public function test_has_body_is_false_for_null_empty_and_whitespace_body(): void
    {
        $this->assertFalse((new VandarResponse(200))->hasBody());
        $this->assertFalse((new VandarResponse(200, body: ''))->hasBody());
        $this->assertFalse((new VandarResponse(200, body: " \n\t "))->hasBody());
        $this->assertTrue((new VandarResponse(200, body: 'ok'))->hasBody());
    }

    public function test_json_parse_failed_returns_stored_flag(): void
    {
        $this->assertTrue((new VandarResponse(200, jsonParseFailed: true))->jsonParseFailed());
        $this->assertFalse((new VandarResponse(200))->jsonParseFailed());
    }

    public function test_content_type_reads_header_case_insensitively(): void
    {
        $response = new VandarResponse(200, headers: [
            'content-type' => ['application/json; charset=utf-8'],
        ]);

        $this->assertSame('application/json; charset=utf-8', $response->contentType());
    }

    public function test_is_json_detects_json_content_types(): void
    {
        $json = new VandarResponse(200, headers: ['Content-Type' => ['application/json']]);
        $problemJson = new VandarResponse(200, headers: ['Content-Type' => ['application/problem+json']]);
        $html = new VandarResponse(200, headers: ['Content-Type' => ['text/html']]);

        $this->assertTrue($json->isJson());
        $this->assertTrue($problemJson->isJson());
        $this->assertFalse($html->isJson());
    }

    public function test_redacted_body_redacts_sensitive_json_and_plain_text_values(): void
    {
        $response = new VandarResponse(200, body: implode("\n", [
            '{"token":"fake-token","apiKey":"fake-api-key","cardNumber":"fake-card","email":"fake@example.test"}',
            'Authorization: Bearer fake-authorization-token',
            'iban=fake-iban&sheba=fake-sheba&mobile=fake-mobile',
        ]));

        $redactedBody = $response->redactedBody();

        $this->assertIsString($redactedBody);
        $this->assertStringContainsString('[REDACTED]', $redactedBody);
        $this->assertStringNotContainsString('fake-token', $redactedBody);
        $this->assertStringNotContainsString('fake-api-key', $redactedBody);
        $this->assertStringNotContainsString('fake-card', $redactedBody);
        $this->assertStringNotContainsString('fake@example.test', $redactedBody);
        $this->assertStringNotContainsString('fake-authorization-token', $redactedBody);
        $this->assertStringNotContainsString('fake-iban', $redactedBody);
        $this->assertStringNotContainsString('fake-sheba', $redactedBody);
        $this->assertStringNotContainsString('fake-mobile', $redactedBody);
    }

    public function test_to_array_does_not_expose_raw_sensitive_body_values(): void
    {
        $response = new VandarResponse(
            status: 500,
            headers: ['Content-Type' => ['application/json']],
            body: '{"token":"fake-secret-token"}',
            jsonParseFailed: true,
        );

        $array = $response->toArray();
        $encoded = json_encode($array);

        $this->assertArrayHasKey('redacted_body', $array);
        $this->assertArrayNotHasKey('body', $array);
        $this->assertSame(true, $array['json_parse_failed']);
        $this->assertSame('application/json', $array['content_type']);
        $this->assertIsString($encoded);
        $this->assertStringNotContainsString('fake-secret-token', $encoded);
        $this->assertStringContainsString('[REDACTED]', $encoded);
    }

    public function test_json_and_to_array_preserve_parsed_values_that_apps_must_redact_before_logging(): void
    {
        $response = new VandarResponse(
            status: 200,
            json: [
                'token' => 'fake-token',
                'factorNumber' => 'fake-factor-number',
                'transaction_id' => 'fake-transaction-id',
                'status' => 'ok',
            ],
            headers: [
                'Authorization' => ['Bearer fake-authorization-token'],
            ],
            body: '{"token":"fake-token","factorNumber":"fake-factor-number","status":"ok"}',
        );

        $array = $response->toArray();

        $this->assertSame('fake-token', $response->json('token'));
        $this->assertSame('fake-factor-number', $array['json']['factorNumber']);
        $this->assertSame('fake-transaction-id', $array['json']['transaction_id']);
        $this->assertSame(['Bearer fake-authorization-token'], $array['headers']['Authorization']);
        $this->assertSame('ok', $array['json']['status']);
        $this->assertStringNotContainsString('fake-token', (string) $array['redacted_body']);
        $this->assertStringNotContainsString('fake-factor-number', (string) $array['redacted_body']);
        $this->assertStringContainsString('ok', (string) $array['redacted_body']);
    }

    public function test_new_constructor_parameters_are_optional(): void
    {
        $response = new VandarResponse(200, ['ok' => true], ['X-Test' => ['yes']]);

        $this->assertSame(['ok' => true], $response->json());
        $this->assertSame(['X-Test' => ['yes']], $response->headers());
        $this->assertNull($response->body());
        $this->assertFalse($response->jsonParseFailed());
    }

    public function test_rate_limit_helpers_read_retry_after_headers(): void
    {
        $response = new VandarResponse(429, headers: [
            'Retry-After' => ['2'],
        ]);

        $this->assertTrue($response->tooManyRequests());
        $this->assertTrue($response->rateLimited());
        $this->assertSame(2, $response->retryAfter());
    }

    public function test_retry_after_parses_http_date_header(): void
    {
        $response = new VandarResponse(429, headers: [
            'retry-after' => [gmdate('D, d M Y H:i:s \G\M\T', time() + 120)],
        ]);

        $retryAfter = $response->retryAfter();

        $this->assertIsInt($retryAfter);
        $this->assertGreaterThanOrEqual(0, $retryAfter);
        $this->assertLessThanOrEqual(120, $retryAfter);
    }

    public function test_invalid_retry_after_returns_null(): void
    {
        $response = new VandarResponse(429, headers: [
            'Retry-After' => ['not-a-date'],
        ]);

        $this->assertNull($response->retryAfter());
    }

    /**
     * @param  class-string<\Throwable>  $exception
     */
    #[DataProvider('exceptionStatusProvider')]
    public function test_throw_maps_failed_status_codes_to_exceptions(int $status, string $exception): void
    {
        $this->expectException($exception);

        (new VandarResponse($status, ['message' => 'Failed']))->throw();
    }

    /**
     * @return array<string, array{int, class-string<\Throwable>}>
     */
    public static function exceptionStatusProvider(): array
    {
        return [
            'authentication' => [401, VandarAuthenticationException::class],
            'authorization' => [403, VandarAuthorizationException::class],
            'validation' => [422, VandarValidationException::class],
            'rate limit' => [429, VandarRateLimitException::class],
            'server' => [500, VandarServerException::class],
            'generic' => [400, VandarRequestException::class],
        ];
    }

    public function test_exception_context_redacts_sensitive_response_fields(): void
    {
        try {
            (new VandarResponse(422, [
                'message' => 'Invalid',
                'access_token' => 'fake-access-token',
                'refresh_token' => 'fake-refresh-token',
            ]))->throw();
        } catch (VandarRequestException $exception) {
            $encodedContext = json_encode($exception->context());

            $this->assertIsString($encodedContext);
            $this->assertStringNotContainsString('fake-access-token', $encodedContext);
            $this->assertStringNotContainsString('fake-refresh-token', $encodedContext);
            $this->assertStringContainsString('[redacted]', $encodedContext);

            return;
        }

        $this->fail('Expected Vandar request exception was not thrown.');
    }
}
