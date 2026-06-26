<?php

declare(strict_types=1);

namespace Zarbinco\LaravelVandar\Exceptions;

use Throwable;
use Zarbinco\LaravelVandar\Support\SensitiveDataRedactor;

class VandarTokenRefreshException extends VandarTokenException
{
    /**
     * @param  array<string, mixed>  $response
     */
    public function __construct(
        string $message = 'Unable to refresh Vandar token.',
        int $status = 0,
        array $response = [],
        ?Throwable $previous = null,
        array $context = [],
    ) {
        parent::__construct(
            message: self::safeMessage($message),
            code: $status,
            previous: $previous,
            context: array_merge([
                'status' => $status,
                'message' => 'Unable to refresh Vandar token.',
                'response' => SensitiveDataRedactor::redact($response),
            ], SensitiveDataRedactor::redact($context)),
        );
    }

    private static function safeMessage(string $message): string
    {
        return $message === '' ? 'Unable to refresh Vandar token.' : 'Unable to refresh Vandar token.';
    }
}
