<?php

declare(strict_types=1);

namespace Zarbinco\LaravelVandar\Testing;

use Illuminate\Support\Facades\Http;

final class FakeVandarResponseFactory
{
    public static function make(mixed $definition): mixed
    {
        if (is_array($definition) && self::looksLikeResponseDefinition($definition)) {
            $body = $definition['body'] ?? [];
            $status = is_numeric($definition['status'] ?? null) ? (int) $definition['status'] : 200;
            $headers = is_array($definition['headers'] ?? null) ? $definition['headers'] : [];

            return Http::response(
                is_array($body) || is_string($body) ? $body : [],
                $status,
                $headers,
            );
        }

        return Http::response(is_array($definition) ? $definition : [], 200);
    }

    /**
     * @param  array<string, mixed>  $definition
     */
    private static function looksLikeResponseDefinition(array $definition): bool
    {
        return array_key_exists('body', $definition)
            || array_key_exists('status', $definition)
            || array_key_exists('headers', $definition);
    }
}
