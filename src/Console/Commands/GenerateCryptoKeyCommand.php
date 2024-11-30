<?php

namespace Akira\LaravelCrypto\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Console\Command\Command as CommandAlias;

class GenerateCryptoKeyCommand extends Command
{
    protected $signature = 'crypto:generate-key {--length=64 : The length of the key in bytes (default: 64)}';

    protected $description = 'Generate a 64-byte encryption key';

    public function handle(): int
    {

        try {
            $length = (int) $this->option('length');

            if ($length < 32 || $length > 64) {
                $this->error('The key length must be between 32 and 64 bytes.');

                return CommandAlias::FAILURE;
            }

            $key = base64_encode(random_bytes($length));

            $this->info('Your encryption key is:');
            $this->line($key);

            $this->comment('You can update your .env file with the following line:');
            $this->line("CRYPTO_ENCRYPTION_KEY={$key}");

            return CommandAlias::SUCCESS;
        } catch (\Exception $e) {
            $this->error('An error occurred while generating the key: '
                .$e->getMessage());

            return CommandAlias::FAILURE;
        }
    }
}
