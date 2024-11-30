<?php

namespace Akira\LaravelCrypto;

use Exception;

class LaravelCrypto
{
    public int $iterations;

    private string $algorithm;

    private string $key;

    private readonly int $keySize;

    public function __construct()
    {
        $this->algorithm = config('crypto.algorithm');
        $this->key = config('crypto.encryption-key');
        $this->keySize = (int) config('crypto.key_size');
        $this->iterations = (int) config('crypto.iterations');
    }

    public static function make(): self
    {
        return app(self::class);
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function setKey(string $key): self
    {
        $this->key = $key;

        return $this;
    }

    public function getAlgorithm(): string
    {
        return $this->algorithm;
    }

    public function setAlgorithm(string $algorithm): self
    {
        $this->algorithm = $algorithm;

        return $this;
    }

    public function decrypt(string $data): string|false
    {
        try {

            [$iv, $encryptedData] = $this->decodeAndValidateData($data);

            $decrypted = openssl_decrypt(
                data: $encryptedData,
                cipher_algo: $this->algorithm,
                passphrase: $this->deriveKey(), // The derived key from the stored key
                options: OPENSSL_RAW_DATA,
                iv: $iv
            );

            if ($decrypted === false) {
                while ($msg = openssl_error_string()) {
                    error_log('OpenSSL Decrypt Error: '.$msg);
                }

                return false;
            }

            return $decrypted;

        } catch (Exception $e) {

            error_log('Decryption error: '.$e->getMessage());

            return false;
        }
    }

    public function encrypt(string $data): string
    {
        $iv = $this->generateRandomIV();

        $encryptedData = $this->encryptData($data, $iv);

        return base64_encode($iv.$encryptedData);
    }

    /**
     * @throws Exception
     */
    private function decodeAndValidateData(string $data): array
    {
        $decodedData = $this->decodeData($data);

        [$iv, $encryptedData] = $this->extractDecryptionComponents($decodedData);

        $this->validateDecryptionComponents($iv, $encryptedData);

        return [$iv, $encryptedData];
    }

    /**
     * @throws Exception
     */
    private function decodeData(string $data): string
    {
        $decodedData = base64_decode($data);

        if ($decodedData === '' || $decodedData === '0') {
            throw new Exception('Invalid data encoding');
        }

        return $decodedData;
    }

    private function extractDecryptionComponents(string $decodedData): array
    {
        $ivLength = openssl_cipher_iv_length($this->algorithm);

        $iv = mb_substr($decodedData, 0, $ivLength, '8bit'); // '8bit' ensures binary-safe substring.

        $encryptedData = mb_substr($decodedData, $ivLength, null, '8bit');

        return [$iv, $encryptedData];
    }

    /**
     * @throws Exception
     */
    private function validateDecryptionComponents($iv, $encryptedData): void
    {
        if (! $iv || ! $encryptedData) {
            throw new Exception('Decryption error: missing components');
        }
    }

    /**
     * @throws Exception
     */
    private function deriveKey(): string
    {
        if ($this->hasShortKey()) {
            throw new Exception('Encryption key length is too short');
        }

        $salt = $this->getSalt();

        return $this->hashSaltedKey($salt);
    }

    private function hasShortKey(): bool
    {
        return mb_strlen($this->key) < $this->keySize * 2;
    }

    private function getSalt(): string
    {
        return mb_substr($this->key, $this->keySize);
    }

    private function hashSaltedKey(string $salt): string
    {
        $key = mb_substr($this->key, 0, $this->keySize);

        return hash_pbkdf2('sha256', $key, $salt, $this->iterations, $this->keySize,
            true);
    }

    private function generateRandomIV(): string
    {
        return random_bytes(openssl_cipher_iv_length($this->algorithm));
    }

    private function encryptData(string $data, string $iv): string|false
    {
        return openssl_encrypt(
            data: $data,
            cipher_algo: $this->algorithm,
            passphrase: $this->deriveKey(),
            options: OPENSSL_RAW_DATA,
            iv: $iv
        );
    }
}
