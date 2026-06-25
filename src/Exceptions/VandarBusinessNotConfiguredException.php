<?php

declare(strict_types=1);

namespace Zarbinco\LaravelVandar\Exceptions;

class VandarBusinessNotConfiguredException extends VandarException
{
    public function __construct()
    {
        parent::__construct('Vandar business is not configured.');
    }
}
