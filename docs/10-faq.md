# Frequently Asked Questions (FAQ)

## General Questions

### What is Laravel Crypto?

Laravel Crypto is a Laravel package that provides secure data encryption and decryption using modern cryptographic algorithms like AES-256-CBC. It features PBKDF2 key derivation, randomized IVs, and seamless Laravel integration.

### How is it different from Laravel's built-in encryption?

While Laravel includes encryption capabilities, Laravel Crypto offers:
- Separate encryption keys from your application key
- PBKDF2 key derivation for enhanced security
- Configurable algorithms and iterations
- Simplified API specifically designed for data encryption
- Additional security features and customization options

### Is Laravel Crypto production-ready?

Yes, Laravel Crypto is production-ready and uses industry-standard encryption algorithms. However, always:
- Use strong encryption keys
- Follow security best practices
- Test thoroughly in your specific use case
- Keep the package updated

### What encryption algorithms are supported?

- **AES-256-CBC** (recommended, default)
- **AES-128-CBC**
- **AES-192-CBC**
- **AES-256-GCM** (authenticated encryption)
- **AES-128-GCM**
- Any algorithm supported by your OpenSSL installation

Check available algorithms:
```php
dd(openssl_get_cipher_methods());
```

## Installation & Setup

### Do I need to install OpenSSL separately?

OpenSSL extension is typically included with PHP, but you may need to enable it:

**Check if installed:**
```bash
php -m | grep openssl
```

**If not installed:**
- Ubuntu/Debian: `sudo apt-get install php-openssl`
- macOS: Usually pre-installed
- Windows: Uncomment `extension=openssl` in `php.ini`

### Can I use Laravel Crypto without publishing the config?

Yes! The package works with default configuration. Publishing is optional and only needed if you want to customize settings.

```bash
# Optional
php artisan vendor:publish --tag="crypto-config"
```

### How do I generate a secure encryption key?

Use the provided artisan command:

```bash
php artisan crypto:generate-key
```

For specific key length:
```bash
php artisan crypto:generate-key --length=64
```

### Can I use the same key as APP_KEY?

While possible, it's not recommended. Use separate keys for different purposes:

```env
APP_KEY=your-app-key
CRYPTO_ENCRYPTION_KEY=your-crypto-key
```

## Usage Questions

### How do I encrypt data?

```php
use Akira\LaravelCrypto\Facades\Crypto;

$encrypted = Crypto::encrypt('sensitive data');
```

### How do I decrypt data?

```php
$decrypted = Crypto::decrypt($encrypted);

if ($decrypted === false) {
    // Decryption failed
}
```

### Can I encrypt arrays or objects?

Yes, convert them to JSON first:

```php
// Encrypt array
$data = ['key' => 'value'];
$encrypted = Crypto::encrypt(json_encode($data));

// Decrypt array
$decrypted = json_decode(Crypto::decrypt($encrypted), true);
```

### Can I encrypt files?

Yes:

```php
$content = file_get_contents('file.txt');
$encrypted = Crypto::encrypt($content);
Storage::put('encrypted.txt', $encrypted);

// Decrypt
$encrypted = Storage::get('encrypted.txt');
$decrypted = Crypto::decrypt($encrypted);
file_put_contents('decrypted.txt', $decrypted);
```

### How do I use it in Eloquent models?

Use accessors and mutators:

```php
use Akira\LaravelCrypto\Facades\Crypto;

class User extends Model
{
    public function setSsnAttribute($value)
    {
        $this->attributes['ssn'] = Crypto::encrypt($value);
    }
    
    public function getSsnAttribute($value)
    {
        return Crypto::decrypt($value);
    }
}
```

### Can I change the encryption algorithm at runtime?

Yes:

```php
$encrypted = Crypto::setAlgorithm('AES-128-CBC')
    ->encrypt('data');
```

### Can I use different keys for different data?

Yes:

```php
$encrypted1 = Crypto::setKey('key-1')->encrypt('data1');
$encrypted2 = Crypto::setKey('key-2')->encrypt('data2');
```

## Security Questions

### Is AES-256-CBC secure?

Yes, AES-256-CBC is a well-established, secure encryption algorithm when used correctly. Laravel Crypto uses:
- Randomized IVs for each encryption
- PBKDF2 key derivation
- Proper padding and encoding

### Why does encrypting the same data produce different results?

This is intentional and secure! Each encryption uses a unique, random IV (Initialization Vector), which ensures:
- Same plaintext produces different ciphertext
- Prevents pattern analysis attacks
- Enhances overall security

```php
$encrypted1 = Crypto::encrypt('hello');
$encrypted2 = Crypto::encrypt('hello');

// $encrypted1 !== $encrypted2 (this is good!)
```

### Can someone decrypt my data without the key?

No. Without the encryption key, the data is cryptographically secure. However:
- Use strong, random keys
- Keep keys secret
- Follow security best practices
- Rotate keys periodically

### Should I encrypt passwords?

**No!** Passwords should be **hashed**, not encrypted:

```php
// For passwords - use hashing
$hashed = Hash::make($password);

// For data that needs to be decrypted - use encryption
$encrypted = Crypto::encrypt($ssn);
```

### What happens if I lose my encryption key?

**Data is unrecoverable.** Without the key, encrypted data cannot be decrypted. Always:
- Backup keys securely
- Store keys in secure location (not in version control)
- Use environment variables
- Consider key escrow for critical data

### How often should I rotate encryption keys?

Recommendations:
- **Development:** Not necessary
- **Staging:** Every 6-12 months
- **Production:** Every 3-6 months
- **After breach:** Immediately

Implement gradual key rotation to avoid data loss.

## Performance Questions

### Is encryption slow?

Encryption has computational cost but is generally fast:
- Simple strings: < 1ms
- Large data (1MB): 10-50ms
- Depends on PBKDF2 iterations

Optimize by:
- Reducing iterations (with security trade-off)
- Using faster algorithm (AES-128 vs AES-256)
- Caching decrypted data
- Encrypting only necessary data

### How much does encrypted data grow?

Approximate size increase:
- Base64 encoding: +33%
- IV storage: +16 bytes (CBC mode)
- **Total:** ~35-40% larger

Example:
```php
$original = 'hello world'; // 11 bytes
$encrypted = Crypto::encrypt($original); // ~30 bytes
```

### Can I use Laravel Crypto for large files?

Yes, but process in chunks for very large files:

```php
// For files < 10MB: direct encryption is fine
$encrypted = Crypto::encrypt(file_get_contents('file.pdf'));

// For files > 10MB: use chunking
// See advanced usage documentation
```

### Does it work with queued jobs?

Yes:

```php
class ProcessData implements ShouldQueue
{
    public string $encryptedData;
    
    public function __construct($data)
    {
        $this->encryptedData = Crypto::encrypt(json_encode($data));
    }
    
    public function handle()
    {
        $data = json_decode(Crypto::decrypt($this->encryptedData), true);
        // Process...
    }
}
```

## Database Questions

### What column type should I use?

Use **TEXT** or **MEDIUMTEXT**:

```php
Schema::table('users', function (Blueprint $table) {
    $table->text('encrypted_field');
    // or for very large data
    $table->mediumText('encrypted_field');
});
```

### Can I query encrypted data?

No direct queries on encrypted data. Use hash indexing:

```php
// Migration
$table->text('ssn');
$table->string('ssn_hash', 64)->index();

// Model
public function setSsnAttribute($value)
{
    $this->attributes['ssn'] = Crypto::encrypt($value);
    $this->attributes['ssn_hash'] = hash('sha256', $value);
}

// Query
$user = User::where('ssn_hash', hash('sha256', $searchSSN))->first();
```

### Can I search encrypted data?

Not directly. Options:
1. **Hash indexing** (exact matches)
2. **Searchable encryption** (complex, not included)
3. **Decrypt and filter** (application-level, slow)
4. **Separate searchable fields** (partial data unencrypted)

### Should I encrypt all columns?

No! Only encrypt sensitive data:
- ✅ SSN, credit cards, medical records
- ✅ Personal information
- ❌ IDs, timestamps, public data
- ❌ Data needed for queries/sorting

## Compatibility Questions

### What Laravel versions are supported?

- Laravel 11.x ✅
- Laravel 12.x ✅

### What PHP versions are supported?

- PHP 8.2+ ✅
- PHP 8.3+ ✅

### Does it work with Lumen?

The package is designed for Laravel. For Lumen, you may need to:
1. Register service provider manually
2. Load configuration manually
3. Test thoroughly

### Can I use it in a package?

Yes:

```php
use Akira\LaravelCrypto\LaravelCrypto;

class MyPackage
{
    private LaravelCrypto $crypto;
    
    public function __construct()
    {
        $this->crypto = new LaravelCrypto([
            'encryption_key' => config('mypackage.crypto_key'),
            'algorithm' => 'AES-256-CBC',
            'key_size' => 32,
            'interactions' => 10000,
        ]);
    }
}
```

### Does it work with Octane?

Yes, Laravel Crypto is compatible with Laravel Octane. The service is registered as a singleton.

## Troubleshooting Questions

### Why does decryption return false?

Common reasons:
1. **Wrong key** - Using different key than encryption
2. **Data corruption** - Data modified during storage/transmission
3. **Algorithm mismatch** - Different algorithm used
4. **Invalid base64** - Data not properly encoded

Debug:
```php
logger()->debug('Key: ' . substr(Crypto::getKey(), 0, 10));
logger()->debug('Algorithm: ' . Crypto::getAlgorithm());
logger()->debug('Encrypted length: ' . strlen($encrypted));
```

### Why is encryption slow?

Possible causes:
1. **High iteration count** - Reduce `CRYPTO_INTERACTIONS`
2. **Large data** - Process in chunks
3. **Weak hardware** - Consider faster servers

Optimize:
```env
CRYPTO_INTERACTIONS=5000  # Lower for speed
CRYPTO_CIPHER=AES-128-CBC  # Faster algorithm
```

### Why do I get "Class not found" errors?

1. **Clear cache:**
```bash
php artisan optimize:clear
```

2. **Check namespace:**
```php
// Correct
use Akira\LaravelCrypto\Facades\Crypto;

// Wrong
use Illuminate\Support\Facades\Crypto;
```

3. **Verify installation:**
```bash
composer show akira/laravel-crypto
```

## Migration Questions

### How do I migrate from Laravel's Crypt?

```php
use Illuminate\Support\Facades\Crypt;
use Akira\LaravelCrypto\Facades\Crypto as LaravelCrypto;

// Decrypt with Laravel's Crypt
$decrypted = Crypt::decrypt($oldEncrypted);

// Re-encrypt with Laravel Crypto
$newEncrypted = LaravelCrypto::encrypt($decrypted);

// Update database
$user->update(['ssn' => $newEncrypted]);
```

### How do I migrate existing encrypted data to new key?

```php
use Akira\LaravelCrypto\Facades\Crypto;

$oldKey = 'old-key';
$newKey = 'new-key';

User::chunk(100, function ($users) use ($oldKey, $newKey) {
    foreach ($users as $user) {
        $decrypted = Crypto::setKey($oldKey)->decrypt($user->ssn);
        
        if ($decrypted !== false) {
            $user->ssn = Crypto::setKey($newKey)->encrypt($decrypted);
            $user->save();
        }
    }
});
```

## Compliance Questions

### Is it GDPR compliant?

Laravel Crypto provides the encryption tools, but GDPR compliance depends on your implementation:

✅ Do:
- Encrypt personal data
- Implement right to erasure
- Log data access
- Use secure key management

See [Security Best Practices](security-best-practices.md#compliance) for details.

### Is it HIPAA compliant?

Yes, when configured correctly:

```php
$crypto = new LaravelCrypto([
    'algorithm' => 'AES-256-GCM', // Authenticated encryption
    'encryption_key' => env('HIPAA_KEY'),
    'key_size' => 32,
    'interactions' => 100000, // High security
]);
```

Also implement:
- Audit logging
- Access controls
- Key management
- Regular security reviews

### Is it PCI DSS compliant?

For payment card data:

✅ Do:
- Use AES-256 encryption
- Never store CVV
- Implement access controls
- Log all access
- Regular key rotation

❌ Don't:
- Store full card numbers unless absolutely necessary
- Use weak keys
- Log decrypted card data

## Getting Help

### Where can I find more examples?

- [Examples Documentation](06-examples.md)
- [Advanced Usage](05-advanced-usage.md)
- [API Reference](07-api-reference.md)

### How do I report a bug?

1. Check [existing issues](https://github.com/akira/laravel-crypto/issues)
2. Create new issue with:
   - Laravel version
   - PHP version
   - Error message
   - Steps to reproduce

### How do I contribute?

1. Fork the repository
2. Create feature branch
3. Make changes
4. Add tests
5. Submit pull request

### Is commercial support available?

Check the package repository for commercial support options or contact the maintainer directly.

## Next Steps

- [Read the full documentation](00-index.md)
- [See practical examples](06-examples.md)
- [Learn security best practices](08-security-best-practices.md)
- [Check troubleshooting guide](09-troubleshooting.md)
