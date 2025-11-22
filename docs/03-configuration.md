# Configuration

## Configuration File

After publishing the configuration, you'll find `config/crypto.php` with all available options.

## Configuration Options

### Encryption Key

```php
'encryption_key' => env('CRYPTO_ENCRYPTION_KEY', 'fdas'),
```

**Description**: The primary key used for encryption and decryption.

**Environment Variable**: `CRYPTO_ENCRYPTION_KEY`

**Best Practices**:
- Use a minimum of 64 characters (32 bytes when decoded)
- Generate using `php artisan crypto:generate-key`
- Store securely in `.env` file
- Never commit keys to version control
- Use different keys for different environments

**Example**:
```env
CRYPTO_ENCRYPTION_KEY=SGVsbG8gV29ybGQgdGhpcyBpcyBhIHNlY3VyZSBrZXkgZm9yIGVuY3J5cHRpb24=
```

### Algorithm

```php
'algorithm' => env('CRYPTO_CIPHER', 'AES-256-CBC'),
```

**Description**: The encryption algorithm to use.

**Environment Variable**: `CRYPTO_CIPHER`

**Supported Algorithms**:
- `AES-256-CBC` (default, recommended)
- `AES-128-CBC`
- `AES-192-CBC`
- `AES-256-GCM` (authenticated encryption)
- `AES-128-GCM`

**Example**:
```env
CRYPTO_CIPHER=AES-256-CBC
```

**Choosing an Algorithm**:
- **AES-256-CBC**: Best balance of security and performance (recommended)
- **AES-128-CBC**: Faster, still secure for most applications
- **AES-256-GCM**: Provides authentication, prevents tampering

### Tag Length

```php
'tag_length' => env('CRYPTO_TAG_LENGTH', 16),
```

**Description**: Authentication tag length for AEAD algorithms (like GCM).

**Environment Variable**: `CRYPTO_TAG_LENGTH`

**Default**: 16 bytes

**Valid Range**: 4-16 bytes

**Note**: Only applicable for authenticated encryption modes (GCM, CCM).

**Example**:
```env
CRYPTO_TAG_LENGTH=16
```

### IV Length

```php
'iv_length' => env('CRYPTO_IV_LENGTH', 16),
```

**Description**: Length of the initialization vector.

**Environment Variable**: `CRYPTO_IV_LENGTH`

**Default**: 16 bytes (automatically determined by algorithm)

**Note**: This is typically determined by the algorithm and doesn't need manual configuration.

### Key Size

```php
'key_size' => env('CRYPTO_KEY_SIZE', 32),
```

**Description**: The size of the encryption key in bytes.

**Environment Variable**: `CRYPTO_KEY_SIZE`

**Default**: 32 bytes (256 bits)

**Common Values**:
- **32 bytes**: For AES-256 (recommended)
- **24 bytes**: For AES-192
- **16 bytes**: For AES-128

**Example**:
```env
CRYPTO_KEY_SIZE=32
```

### Iterations (Key Derivation)

```php
'interactions' => env('CRYPTO_INTERACTIONS', 10000),
```

**Description**: Number of iterations for PBKDF2 key derivation.

**Environment Variable**: `CRYPTO_INTERACTIONS`

**Default**: 10,000

**Recommended Range**: 10,000 - 100,000

**Considerations**:
- **Higher values**: More secure, but slower
- **Lower values**: Faster, but less secure
- **10,000**: Good balance for most applications
- **100,000**: High-security applications

**Example**:
```env
CRYPTO_INTERACTIONS=50000
```

## Complete Configuration Example

### config/crypto.php

```php
<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Encryption Key
    |--------------------------------------------------------------------------
    */
    'encryption_key' => env('CRYPTO_ENCRYPTION_KEY', ''),

    /*
    |--------------------------------------------------------------------------
    | Encryption Algorithm
    |--------------------------------------------------------------------------
    */
    'algorithm' => env('CRYPTO_CIPHER', 'AES-256-CBC'),

    /*
    |--------------------------------------------------------------------------
    | Tag Length (for AEAD Algorithms)
    |--------------------------------------------------------------------------
    */
    'tag_length' => env('CRYPTO_TAG_LENGTH', 16),

    /*
    |--------------------------------------------------------------------------
    | Initialization Vector (IV) Length
    |--------------------------------------------------------------------------
    */
    'iv_length' => env('CRYPTO_IV_LENGTH', 16),

    /*
    |--------------------------------------------------------------------------
    | Key Size
    |--------------------------------------------------------------------------
    */
    'key_size' => env('CRYPTO_KEY_SIZE', 32),

    /*
    |--------------------------------------------------------------------------
    | Key Derivation Iterations
    |--------------------------------------------------------------------------
    */
    'interactions' => env('CRYPTO_INTERACTIONS', 10000),
];
```

### .env Configuration

```env
# Laravel Crypto Configuration
CRYPTO_ENCRYPTION_KEY=SGVsbG8gV29ybGQgdGhpcyBpcyBhIHNlY3VyZSBrZXkgZm9yIGVuY3J5cHRpb24hISE=
CRYPTO_CIPHER=AES-256-CBC
CRYPTO_KEY_SIZE=32
CRYPTO_INTERACTIONS=10000
```

## Environment-Specific Configuration

### Development Environment

```env
# .env.local or .env.development
CRYPTO_ENCRYPTION_KEY=dev_key_here_not_for_production
CRYPTO_CIPHER=AES-256-CBC
CRYPTO_INTERACTIONS=10000  # Lower for faster development
```

### Production Environment

```env
# .env.production
CRYPTO_ENCRYPTION_KEY=super_secure_production_key_here
CRYPTO_CIPHER=AES-256-CBC
CRYPTO_INTERACTIONS=50000  # Higher for production security
```

### Testing Environment

```env
# .env.testing
CRYPTO_ENCRYPTION_KEY=test_key_consistent_for_testing
CRYPTO_CIPHER=AES-256-CBC
CRYPTO_INTERACTIONS=1000  # Lower for faster tests
```

## Runtime Configuration

You can change configuration at runtime:

### Change Algorithm

```php
use Akira\LaravelCrypto\Facades\Crypto;

$crypto = Crypto::setAlgorithm('AES-128-CBC');
$encrypted = $crypto->encrypt('data');
```

### Change Key

```php
use Akira\LaravelCrypto\Facades\Crypto;

$crypto = Crypto::setKey('your-custom-key');
$encrypted = $crypto->encrypt('data');
```

### Get Current Configuration

```php
use Akira\LaravelCrypto\Facades\Crypto;

$algorithm = Crypto::getAlgorithm();  // Returns: 'AES-256-CBC'
$key = Crypto::getKey();              // Returns: current key
```

## Multiple Configurations

You can create different instances with different configurations:

```php
use Akira\LaravelCrypto\LaravelCrypto;

// Standard configuration
$standardCrypto = new LaravelCrypto(config('crypto'));

// High-security configuration
$highSecurityCrypto = new LaravelCrypto([
    'encryption_key' => env('HIGH_SECURITY_KEY'),
    'algorithm' => 'AES-256-GCM',
    'key_size' => 32,
    'interactions' => 100000,
]);

// Fast configuration (for less sensitive data)
$fastCrypto = new LaravelCrypto([
    'encryption_key' => env('FAST_CRYPTO_KEY'),
    'algorithm' => 'AES-128-CBC',
    'key_size' => 16,
    'interactions' => 1000,
]);
```

## Configuration Validation

### Validate Key Length

```php
use Akira\LaravelCrypto\Facades\Crypto;

try {
    $encrypted = Crypto::encrypt('test');
} catch (\Exception $e) {
    if (str_contains($e->getMessage(), 'key length is too short')) {
        // Key is invalid
        logger()->error('Crypto key is too short');
    }
}
```

### Check Algorithm Support

```php
$supportedAlgorithms = openssl_get_cipher_methods();

if (in_array('AES-256-CBC', $supportedAlgorithms)) {
    // Algorithm is supported
}
```

## Security Recommendations

### Key Management

1. **Never hardcode keys** in your application code
2. **Use environment variables** for all sensitive configuration
3. **Rotate keys regularly** in production
4. **Use different keys** for different environments
5. **Store backup keys securely** offline

### Algorithm Selection

1. **Use AES-256-CBC** for general purposes
2. **Use AES-256-GCM** when authentication is needed
3. **Avoid deprecated algorithms** like DES or RC4
4. **Test algorithm performance** in your specific environment

### Iteration Count

1. **Minimum 10,000 iterations** for production
2. **50,000+ iterations** for high-security applications
3. **Balance security vs performance** for your use case
4. **Consider hardware capabilities** when setting iterations

## Performance Tuning

### For High-Throughput Applications

```env
CRYPTO_CIPHER=AES-128-CBC  # Faster than AES-256
CRYPTO_KEY_SIZE=16
CRYPTO_INTERACTIONS=5000   # Lower iterations
```

### For High-Security Applications

```env
CRYPTO_CIPHER=AES-256-GCM  # Authenticated encryption
CRYPTO_KEY_SIZE=32
CRYPTO_INTERACTIONS=100000 # Higher iterations
```

## Troubleshooting Configuration

### Issue: "Encryption key length is too short"

**Solution**: Ensure your key is at least 64 characters:
```bash
php artisan crypto:generate-key --length=64
```

### Issue: Algorithm not supported

**Solution**: Check available algorithms:
```php
dd(openssl_get_cipher_methods());
```

### Issue: Slow encryption performance

**Solution**: Reduce iterations or use faster algorithm:
```env
CRYPTO_INTERACTIONS=5000
CRYPTO_CIPHER=AES-128-CBC
```

## Next Steps

- [Learn basic usage](04-basic-usage.md)
- [Explore advanced features](05-advanced-usage.md)
- [See practical examples](06-examples.md)
