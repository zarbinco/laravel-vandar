<?php

declare(strict_types=1);

namespace Zarbinco\LaravelVandar\Facades;

use Illuminate\Support\Facades\Facade;
use Zarbinco\LaravelVandar\LaravelVandar;

/**
 * @method static string name()
 * @method static string version()
 * @method static mixed config(?string $key = null, mixed $default = null)
 * @method static string baseUrl(string $name = 'api')
 * @method static bool isLoggingEnabled()
 *
 * @see LaravelVandar
 */
final class Vandar extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'vandar';
    }
}
