<?php

declare(strict_types=1);

namespace Akira\LaravelCrypto\Facades;

use Akira\LaravelCrypto\LaravelCrypto;
use Illuminate\Support\Facades\Facade;

/**
 * @see LaravelCrypto
 */
final class Crypto extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return LaravelCrypto::class;
    }
}
