<?php

namespace Akira\LaravelCrypto\Facades;

use Akira\LaravelCrypto\LaravelCrypto;
use Illuminate\Support\Facades\Facade;

/**
 * @see \Akira\LaravelCrypto\LaravelCrypto
 */
class Crypto extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return LaravelCrypto::class;
    }
}
