<?php

declare(strict_types=1);

namespace Akira\LaravelCrypto;

use Akira\LaravelCrypto\Console\Commands\GenerateCryptoKeyCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

final class LaravelCryptoServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-crypto')
            ->hasCommand(GenerateCryptoKeyCommand::class)
            ->hasConfigFile();
    }

    public function register(): void
    {
        parent::register();

        $this->app->singleton(LaravelCrypto::class, fn ($app): LaravelCrypto => new LaravelCrypto(config('crypto')));
    }

    public function boot(): void
    {
        parent::boot();

        if (file_exists($file = __DIR__.'/helpers.php')) {
            require $file;
        }
    }
}
