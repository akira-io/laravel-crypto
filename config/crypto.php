<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Encryption Key
    |--------------------------------------------------------------------------
    |
    | This key is used for encryption and decryption purposes.
    | It is highly recommended to store a secure, 32-byte key in your .env file.
    | If not set, the default application key (APP_KEY) will be used.
    |
    */
    'encryption_key' => env('CRYPTO_ENCRYPTION_KEY', 'fdas'),

    /*
    |--------------------------------------------------------------------------
    | Encryption Algorithm
    |--------------------------------------------------------------------------
    |
    | The encryption algorithm to be used. By default, AES-256-CBC is used, which
    | is secure and widely supported. You can change this to another algorithm
    | (e.g., AES-128-CBC, AES-256-GCM) based on your needs.
    |
    */
    'algorithm' => env('CRYPTO_CIPHER', 'AES-256-CBC'),

    /*
    |--------------------------------------------------------------------------
    | Tag Length (for AEAD Algorithms)
    |--------------------------------------------------------------------------
    |
    | This is the tag length for authenticated encryption algorithms (e.g., AES-GCM).
    | It defines the length of the authentication tag. AES-GCM typically uses 16 bytes.
    | Set this only if you're using authenticated encryption modes.
    |
    */
    'tag_length' => env('CRYPTO_TAG_LENGTH', 16),

    /*
    |--------------------------------------------------------------------------
    | Initialization Vector (IV) Length
    |--------------------------------------------------------------------------
    |
    | The length of the initialization vector (IV) required for the encryption algorithm.
    | For AES-256-CBC, the IV length is usually 16 bytes. This should match the IV length
    | used by your chosen encryption algorithm.
    |
    */
    'iv_length' => env('CRYPTO_IV_LENGTH', 16),

    /*
    |--------------------------------------------------------------------------
    | Key Size
    |--------------------------------------------------------------------------
    |
    | The size of the encryption key. For AES-256 encryption, the key size must be 32 bytes.
    | Ensure that this value is consistent with the algorithm you are using (e.g., 16 bytes for AES-128).
    |
    */
    'key_size' => env('CRYPTO_KEY_SIZE', 32),

    /*
    |--------------------------------------------------------------------------
    | Key Derivation Iterations
    |--------------------------------------------------------------------------
    |
    | The number of iterations used for key derivation. Higher values increase security but
    | also make the encryption slower. The default is 10,000 iterations, which is a good balance.
    |
    */
    'interactions' => env('CRYPTO_INTERACTIONS', 10000),
];
