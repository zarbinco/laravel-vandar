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
