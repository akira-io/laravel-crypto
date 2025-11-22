<?php

declare(strict_types=1);

use Akira\LaravelCrypto\LaravelCrypto;

if (! function_exists('crypto')) {
    function crypto(): LaravelCrypto
    {
        return app(LaravelCrypto::class);
    }
}
