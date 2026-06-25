<?php

declare(strict_types=1);

namespace Zarbinco\LaravelVandar\Support;

final class VandarPath
{
    public static function segment(string|int $value): string
    {
        return rawurlencode((string) $value);
    }

    public static function join(string ...$segments): string
    {
        $normalized = [];

        foreach ($segments as $segment) {
            $segment = trim($segment, '/');

            if ($segment === '') {
                continue;
            }

            $normalized[] = $segment;
        }

        return '/'.implode('/', $normalized);
    }
}
