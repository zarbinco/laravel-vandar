<?php

declare(strict_types=1);

namespace Zarbinco\LaravelVandar\Exceptions;

use Throwable;
use Zarbinco\LaravelVandar\Support\SensitiveDataRedactor;
use Zarbinco\LaravelVandar\Support\SensitiveUrlSanitizer;

class VandarRequestException extends VandarException
{
    /**
     * @param  array<string, mixed>  $response
     */
    public function __construct(
        string $message = 'Vandar request failed.',
        private readonly int $status = 0,
        array $response = [],
        ?Throwable $previous = null,
    ) {
        parent::__construct(
            message: self::safeMessage($message, $status),
            code: $status,
            previous: $previous,
            context: [
                'status' => $status,
                'message' => self::safeMessage($message, $status),
                'response' => self::sanitizeResponse($response),
            ],
        );
    }

    public function status(): int
    {
        return $this->status;
    }

    private static function safeMessage(string $message, int $status): string
    {
        $prefix = $status > 0 ? "Vandar request failed with status {$status}" : 'Vandar request failed';

        return $message === '' ? "{$prefix}." : "{$prefix}.";
    }

    /**
     * @param  array<string, mixed>  $response
     * @return array<string, mixed>
     */
    private static function sanitizeResponse(array $response): array
    {
        $response = SensitiveDataRedactor::redact($response);

        foreach (['body', 'redacted_body'] as $bodyKey) {
            if (isset($response[$bodyKey]) && is_string($response[$bodyKey])) {
                $response[$bodyKey] = SensitiveDataRedactor::redactText($response[$bodyKey]);
            }
        }

        if (isset($response['url']) && is_string($response['url'])) {
            $response['url'] = SensitiveUrlSanitizer::sanitize($response['url']);
        }

        return $response;
    }
}
