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
 * @method static \Zarbinco\LaravelVandar\Http\VandarClient client()
 * @method static \Zarbinco\LaravelVandar\Token\TokenManager tokens()
 * @method static \Zarbinco\LaravelVandar\Resources\RawResource raw()
 * @method static \Zarbinco\LaravelVandar\Resources\BusinessResource business()
 * @method static \Zarbinco\LaravelVandar\Resources\CustomerResource customers()
 * @method static \Zarbinco\LaravelVandar\DTO\VandarResponse response(array $payload = [], int $status = 200, array $headers = [])
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
