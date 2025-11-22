# API Reference

Complete API documentation for Laravel Crypto.

## LaravelCrypto Class

### Namespace

```php
Akira\LaravelCrypto\LaravelCrypto
```

### Constructor

```php
public function __construct(array $config)
```

Creates a new LaravelCrypto instance with the provided configuration.

**Parameters:**
- `$config` (array): Configuration array with the following keys:
  - `algorithm` (string): Encryption algorithm (default: 'AES-256-CBC')
  - `encryption_key` (string): Encryption key
  - `key_size` (int): Key size in bytes (default: 32)
  - `interactions` (int): PBKDF2 iterations (default: 10000)

**Example:**
```php
$crypto = new LaravelCrypto([
    'algorithm' => 'AES-256-CBC',
    'encryption_key' => 'your-key-here',
    'key_size' => 32,
    'interactions' => 10000,
]);
```

---

### make()

```php
public static function make(): self
```

Creates a new instance using the service container.

**Returns:** `LaravelCrypto` instance

**Example:**
```php
$crypto = LaravelCrypto::make();
```

---

### encrypt()

```php
public function encrypt(string $data): string
```

Encrypts the given data.

**Parameters:**
- `$data` (string): The plain text data to encrypt

**Returns:** Base64-encoded encrypted string

**Throws:** `Exception` if encryption key is too short

**Example:**
```php
$encrypted = $crypto->encrypt('Hello World');
// Returns: "YjNsK3RzaGFsZGY4N3NkZmhzZGZo..."
```

**Process:**
1. Generates a random IV (Initialization Vector)
2. Derives encryption key using PBKDF2
3. Encrypts data using OpenSSL
4. Concatenates IV and encrypted data
5. Returns base64-encoded result

---

### decrypt()

```php
public function decrypt(string $data): string|false
```

Decrypts the given encrypted data.

**Parameters:**
- `$data` (string): Base64-encoded encrypted string

**Returns:** Decrypted string on success, `false` on failure

**Example:**
```php
$decrypted = $crypto->decrypt($encrypted);

if ($decrypted === false) {
    // Handle decryption failure
} else {
    echo $decrypted; // "Hello World"
}
```

**Process:**
1. Base64-decodes the input
2. Extracts IV from the beginning
3. Extracts encrypted data
4. Derives encryption key using PBKDF2
5. Decrypts using OpenSSL
6. Returns decrypted string or false

---

### getKey()

```php
public function getKey(): string
```

Gets the current encryption key.

**Returns:** Current encryption key

**Example:**
```php
$key = $crypto->getKey();
```

---

### setKey()

```php
public function setKey(string $key): self
```

Sets a new encryption key.

**Parameters:**
- `$key` (string): New encryption key

**Returns:** `$this` for method chaining

**Example:**
```php
$crypto->setKey('new-encryption-key')
       ->encrypt('data');
```

---

### getAlgorithm()

```php
public function getAlgorithm(): string
```

Gets the current encryption algorithm.

**Returns:** Current algorithm (e.g., 'AES-256-CBC')

**Example:**
```php
$algorithm = $crypto->getAlgorithm();
echo $algorithm; // "AES-256-CBC"
```

---

### setAlgorithm()

```php
public function setAlgorithm(string $algorithm): self
```

Sets a new encryption algorithm.

**Parameters:**
- `$algorithm` (string): New algorithm (e.g., 'AES-256-CBC', 'AES-128-CBC')

**Returns:** `$this` for method chaining

**Example:**
```php
$crypto->setAlgorithm('AES-128-CBC')
       ->encrypt('data');
```

---

## Crypto Facade

### Namespace

```php
Akira\LaravelCrypto\Facades\Crypto
```

The Crypto facade provides static access to all LaravelCrypto methods.

### Available Methods

All methods from the `LaravelCrypto` class are available statically:

```php
use Akira\LaravelCrypto\Facades\Crypto;

// Encryption
$encrypted = Crypto::encrypt('data');

// Decryption
$decrypted = Crypto::decrypt($encrypted);

// Get/Set Key
$key = Crypto::getKey();
Crypto::setKey('new-key');

// Get/Set Algorithm
$algo = Crypto::getAlgorithm();
Crypto::setAlgorithm('AES-128-CBC');

// Static make
$instance = Crypto::make();
```

---

## Service Provider

### Namespace

```php
Akira\LaravelCrypto\LaravelCryptoServiceProvider
```

The service provider registers the package with Laravel.

### Registration

The service provider is automatically discovered by Laravel. It registers:

1. **Singleton Binding**: Binds `LaravelCrypto` as a singleton in the container
2. **Configuration**: Publishes and loads configuration file
3. **Commands**: Registers artisan commands

### Manual Registration

If auto-discovery is disabled:

```php
// config/app.php
'providers' => [
    Akira\LaravelCrypto\LaravelCryptoServiceProvider::class,
],
```

---

## Artisan Commands

### crypto:generate-key

Generates a secure encryption key.

**Signature:**
```bash
php artisan crypto:generate-key {--length=64}
```

**Options:**
- `--length`: Key length in bytes (min: 32, max: 64, default: 64)

**Output:**
```
Your encryption key is:
[base64-encoded-key]

You can update your .env file with the following line:
CRYPTO_ENCRYPTION_KEY=[base64-encoded-key]
```

**Examples:**
```bash
# Generate 64-byte key (default)
php artisan crypto:generate-key

# Generate 32-byte key
php artisan crypto:generate-key --length=32

# Generate 48-byte key
php artisan crypto:generate-key --length=48
```

**Exit Codes:**
- `0`: Success
- `1`: Failure (invalid length or error)

---

## Configuration Reference

### Configuration File Location

```
config/crypto.php
```

### Configuration Keys

#### encryption_key

- **Type:** string
- **Default:** 'fdas'
- **Env:** `CRYPTO_ENCRYPTION_KEY`
- **Description:** Primary encryption key

#### algorithm

- **Type:** string
- **Default:** 'AES-256-CBC'
- **Env:** `CRYPTO_CIPHER`
- **Description:** Encryption algorithm
- **Valid Values:** 
  - 'AES-256-CBC'
  - 'AES-128-CBC'
  - 'AES-192-CBC'
  - 'AES-256-GCM'
  - 'AES-128-GCM'

#### tag_length

- **Type:** integer
- **Default:** 16
- **Env:** `CRYPTO_TAG_LENGTH`
- **Description:** Tag length for AEAD algorithms
- **Range:** 4-16 bytes

#### iv_length

- **Type:** integer
- **Default:** 16
- **Env:** `CRYPTO_IV_LENGTH`
- **Description:** IV length (auto-determined by algorithm)

#### key_size

- **Type:** integer
- **Default:** 32
- **Env:** `CRYPTO_KEY_SIZE`
- **Description:** Encryption key size in bytes
- **Valid Values:** 16 (AES-128), 24 (AES-192), 32 (AES-256)

#### interactions

- **Type:** integer
- **Default:** 10000
- **Env:** `CRYPTO_INTERACTIONS`
- **Description:** PBKDF2 iterations for key derivation
- **Recommended:** 10,000 - 100,000

---

## Helper Functions

Laravel Crypto doesn't provide global helper functions, but you can create your own:

### Custom Helpers

```php
// app/helpers.php

use Akira\LaravelCrypto\Facades\Crypto;

if (!function_exists('encrypt_data')) {
    function encrypt_data($data): string
    {
        return Crypto::encrypt(is_string($data) ? $data : json_encode($data));
    }
}

if (!function_exists('decrypt_data')) {
    function decrypt_data(string $encrypted, $default = null)
    {
        $decrypted = Crypto::decrypt($encrypted);
        
        if ($decrypted === false) {
            return $default;
        }
        
        $decoded = json_decode($decrypted, true);
        return json_last_error() === JSON_ERROR_NONE ? $decoded : $decrypted;
    }
}
```

Register in `composer.json`:

```json
{
    "autoload": {
        "files": [
            "app/helpers.php"
        ]
    }
}
```

Usage:

```php
$encrypted = encrypt_data(['key' => 'value']);
$decrypted = decrypt_data($encrypted, []);
```

---

## Error Handling

### Exception Types

Laravel Crypto may throw standard PHP exceptions:

```php
try {
    $encrypted = Crypto::encrypt($data);
} catch (\Exception $e) {
    // Handle encryption error
    logger()->error('Encryption failed: ' . $e->getMessage());
}
```

### Common Error Messages

#### "Encryption key length is too short"

**Cause:** The encryption key is shorter than required (less than 64 characters)

**Solution:** Generate a proper key using `php artisan crypto:generate-key`

#### "Invalid data encoding"

**Cause:** The encrypted data is not properly base64-encoded

**Solution:** Ensure data wasn't corrupted during storage/transmission

#### "Decryption error: missing components"

**Cause:** The encrypted data is missing IV or encrypted content

**Solution:** Verify data integrity, may indicate corruption

#### OpenSSL Errors

**Cause:** Various OpenSSL-related issues

**Solution:** Check error logs for specific OpenSSL error messages

---

## Type Definitions

### Configuration Array

```php
[
    'algorithm' => 'AES-256-CBC',      // string
    'encryption_key' => 'key',         // string
    'key_size' => 32,                  // int
    'interactions' => 10000,           // int
    'tag_length' => 16,                // int (optional)
    'iv_length' => 16,                 // int (optional)
]
```

### Encrypted Data Format

Encrypted data is returned as a base64-encoded string containing:
```
[IV (variable length)][Encrypted Data (variable length)]
```

The IV length is determined by the encryption algorithm.

---

## Supported Algorithms

### CBC Mode (Cipher Block Chaining)

- **AES-256-CBC** ✅ Recommended
  - Key Size: 32 bytes
  - IV Length: 16 bytes
  - Security: High
  
- **AES-192-CBC**
  - Key Size: 24 bytes
  - IV Length: 16 bytes
  - Security: High
  
- **AES-128-CBC**
  - Key Size: 16 bytes
  - IV Length: 16 bytes
  - Security: Good

### GCM Mode (Galois/Counter Mode - AEAD)

- **AES-256-GCM**
  - Key Size: 32 bytes
  - IV Length: 12 bytes
  - Security: High (with authentication)
  
- **AES-128-GCM**
  - Key Size: 16 bytes
  - IV Length: 12 bytes
  - Security: Good (with authentication)

---

## Performance Characteristics

### Encryption Speed

Approximate operations per second (on typical hardware):

- **AES-128-CBC**: ~50,000 ops/sec
- **AES-256-CBC**: ~35,000 ops/sec
- **AES-256-GCM**: ~30,000 ops/sec

*Note: Actual performance depends on PBKDF2 iterations and hardware*

### Memory Usage

- **Base Memory**: ~1KB per instance
- **Per Operation**: ~2x data size during encryption/decryption

### Size Overhead

- **Base64 Encoding**: +33% size increase
- **IV Storage**: +16 bytes (CBC) or +12 bytes (GCM)
- **Total Overhead**: ~35-40% size increase

---

## Version Compatibility

### Laravel Versions

- Laravel 11.x ✅
- Laravel 12.x ✅

### PHP Versions

- PHP 8.2 ✅
- PHP 8.3 ✅

### Required Extensions

- OpenSSL ✅ Required
- Mbstring ✅ Recommended

---

## Next Steps

- [See practical examples](06-examples.md)
- [Learn security best practices](08-security-best-practices.md)
- [Troubleshooting guide](09-troubleshooting.md)
