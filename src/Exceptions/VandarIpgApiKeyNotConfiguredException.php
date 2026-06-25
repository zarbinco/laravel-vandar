<?php

declare(strict_types=1);

namespace Zarbinco\LaravelVandar\Exceptions;

final class VandarIpgApiKeyNotConfiguredException extends VandarException
{
    public function __construct()
    {
        parent::__construct('Vandar IPG API key is not configured.');
    }
}
