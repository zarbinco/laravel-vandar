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
