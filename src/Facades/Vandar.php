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
 * @method static \Zarbinco\LaravelVandar\Resources\CardResource cards()
 * @method static \Zarbinco\LaravelVandar\Resources\IbanResource ibans()
 * @method static \Zarbinco\LaravelVandar\Resources\InquiryResource inquiries()
 * @method static \Zarbinco\LaravelVandar\Resources\IpgResource ipg()
 * @method static \Zarbinco\LaravelVandar\Resources\RefundResource refunds()
 * @method static \Zarbinco\LaravelVandar\Resources\SettlementResource settlements()
 * @method static \Zarbinco\LaravelVandar\Resources\QueuedSettlementResource queuedSettlements()
 * @method static \Zarbinco\LaravelVandar\Resources\BatchSettlementResource batchSettlements()
 * @method static \Zarbinco\LaravelVandar\Resources\AvandResource avand()
 * @method static \Zarbinco\LaravelVandar\Resources\AvandResource cashIn()
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
