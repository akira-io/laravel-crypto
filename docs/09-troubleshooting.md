# Troubleshooting

Common issues and their solutions.

## Installation Issues

### Issue: Package Not Found

**Error:**
```
Could not find package akira/laravel-crypto
```

**Solutions:**

1. **Clear Composer cache:**
```bash
composer clear-cache
composer require akira/laravel-crypto
```

2. **Check Composer version:**
```bash
composer --version
# Update if needed
composer self-update
```

3. **Verify internet connection:**
```bash
composer diagnose
```

### Issue: OpenSSL Extension Not Found

**Error:**
```
Fatal error: Call to undefined function openssl_encrypt()
```

**Solutions:**

**Ubuntu/Debian:**
```bash
sudo apt-get install php8.2-openssl
sudo service php8.2-fpm restart
```

**macOS:**
```bash
brew install openssl
# OpenSSL is usually included with PHP on macOS
```

**Windows:**
Edit `php.ini` and uncomment:
```ini
extension=openssl
```

**Verify installation:**
```bash
php -m | grep openssl
```

### Issue: Service Provider Not Registered

**Error:**
```
Class 'Crypto' not found
```

**Solutions:**

1. **Clear cache:**
```bash
php artisan config:clear
php artisan cache:clear
php artisan optimize:clear
```

2. **Manual registration (if auto-discovery fails):**

Add to `config/app.php`:
```php
'providers' => [
    Akira\LaravelCrypto\LaravelCryptoServiceProvider::class,
],

'aliases' => [
    'Crypto' => Akira\LaravelCrypto\Facades\Crypto::class,
],
```

3. **Verify package installation:**
```bash
composer show akira/laravel-crypto
```

## Configuration Issues

### Issue: Encryption Key Too Short

**Error:**
```
Exception: Encryption key length is too short
```

**Solution:**

Generate a proper key:
```bash
php artisan crypto:generate-key --length=64
```

Update `.env`:
```env
CRYPTO_ENCRYPTION_KEY=generated-key-here
```

**Minimum Requirements:**
- Key must be at least 64 characters (32 bytes when decoded)
- Use the artisan command to ensure proper entropy

### Issue: Invalid Algorithm

**Error:**
```
Warning: openssl_encrypt(): Unknown cipher algorithm
```

**Solutions:**

1. **Check available algorithms:**
```php
dd(openssl_get_cipher_methods());
```

2. **Use supported algorithm:**
```env
CRYPTO_CIPHER=AES-256-CBC
# or
CRYPTO_CIPHER=AES-128-CBC
```

3. **Common valid algorithms:**
- AES-256-CBC (recommended)
- AES-128-CBC
- AES-256-GCM
- AES-128-GCM

### Issue: Configuration Not Loading

**Error:**
```
Configuration values are default/not working
```

**Solutions:**

1. **Publish configuration:**
```bash
php artisan vendor:publish --tag="crypto-config"
```

2. **Clear config cache:**
```bash
php artisan config:clear
php artisan config:cache
```

3. **Verify .env file:**
```bash
cat .env | grep CRYPTO_
```

4. **Check file permissions:**
```bash
chmod 644 config/crypto.php
chmod 600 .env
```

## Encryption Issues

### Issue: Decryption Returns False

**Error:**
```php
$decrypted = Crypto::decrypt($data);
// $decrypted === false
```

**Possible Causes & Solutions:**

#### 1. Wrong Key
```php
// Ensure using correct key
$key = Crypto::getKey();
logger()->debug('Current key: ' . substr($key, 0, 10) . '...');
```

#### 2. Data Corruption
```php
// Verify data is valid base64
if (!preg_match('/^[a-zA-Z0-9\/+]*={0,2}$/', $encrypted)) {
    logger()->error('Invalid base64 data');
}
```

#### 3. Algorithm Mismatch
```php
// Ensure same algorithm was used for encryption and decryption
$algorithm = Crypto::getAlgorithm();
logger()->debug('Current algorithm: ' . $algorithm);
```

#### 4. Data Was Encrypted with Different Configuration
```php
// Try with original configuration
$originalKey = 'original-key-used-for-encryption';
$decrypted = Crypto::setKey($originalKey)->decrypt($encrypted);
```

### Issue: Encrypted Data Too Long for Column

**Error:**
```
Data too long for column 'encrypted_field'
```

**Solutions:**

1. **Use TEXT column type:**
```php
Schema::table('users', function (Blueprint $table) {
    $table->text('encrypted_field')->change();
});
```

2. **Use MEDIUMTEXT for large data:**
```php
$table->mediumText('encrypted_field');
```

3. **Calculate column size needed:**
```php
$plaintext = 'your data';
$encrypted = Crypto::encrypt($plaintext);
$encryptedLength = strlen($encrypted);

// Encrypted data is ~1.4x original size (base64 + IV)
echo "Need at least: " . $encryptedLength . " bytes";
```

### Issue: Special Characters Not Encrypting Properly

**Error:**
```
Decrypted data contains garbled characters
```

**Solutions:**

1. **Ensure UTF-8 encoding:**
```php
$data = mb_convert_encoding($data, 'UTF-8');
$encrypted = Crypto::encrypt($data);
```

2. **Use json_encode for complex data:**
```php
$data = ['special' => 'chars: éñ中文'];
$encrypted = Crypto::encrypt(json_encode($data));

$decrypted = json_decode(Crypto::decrypt($encrypted), true);
```

3. **Check database charset:**
```php
// In migration
$table->charset = 'utf8mb4';
$table->collation = 'utf8mb4_unicode_ci';
```

## Performance Issues

### Issue: Slow Encryption/Decryption

**Problem:** Operations taking too long

**Solutions:**

1. **Reduce PBKDF2 iterations:**
```env
# Default: 10000
# Reduce for better performance (with security trade-off)
CRYPTO_INTERACTIONS=5000
```

2. **Use faster algorithm:**
```env
# AES-128 is faster than AES-256
CRYPTO_CIPHER=AES-128-CBC
```

3. **Cache decrypted data:**
```php
class CachedDecryption
{
    public function decrypt(string $encrypted, string $cacheKey, int $ttl = 3600)
    {
        return cache()->remember($cacheKey, $ttl, function () use ($encrypted) {
            return Crypto::decrypt($encrypted);
        });
    }
}
```

4. **Batch operations:**
```php
// Instead of decrypting in loop
foreach ($users as $user) {
    $user->decrypted_ssn = Crypto::decrypt($user->ssn);
}

// Use eager loading and process once
$users = User::all();
$decryptedData = [];

foreach ($users as $user) {
    $decryptedData[$user->id] = Crypto::decrypt($user->ssn);
}
```

### Issue: High Memory Usage

**Problem:** Memory limit exceeded during encryption

**Solutions:**

1. **Increase PHP memory limit:**
```php
ini_set('memory_limit', '512M');
```

2. **Process data in chunks:**
```php
class ChunkedEncryption
{
    public function encryptLargeData(string $data): array
    {
        $chunkSize = 1024 * 1024; // 1MB chunks
        $encrypted = [];
        
        for ($i = 0; $i < strlen($data); $i += $chunkSize) {
            $chunk = substr($data, $i, $chunkSize);
            $encrypted[] = Crypto::encrypt($chunk);
        }
        
        return $encrypted;
    }
    
    public function decryptLargeData(array $encryptedChunks): string
    {
        $decrypted = '';
        
        foreach ($encryptedChunks as $chunk) {
            $decrypted .= Crypto::decrypt($chunk);
        }
        
        return $decrypted;
    }
}
```

3. **Stream large files:**
```php
$input = fopen('large-file.txt', 'rb');
$output = fopen('encrypted.txt', 'wb');

while (!feof($input)) {
    $chunk = fread($input, 8192);
    $encrypted = Crypto::encrypt($chunk);
    fwrite($output, $encrypted . "\n");
}

fclose($input);
fclose($output);
```

## Runtime Errors

### Issue: OpenSSL Errors

**Error:**
```
OpenSSL Decrypt Error: error:...
```

**Debug Steps:**

1. **Check OpenSSL errors:**
```php
$encrypted = Crypto::encrypt($data);
$decrypted = openssl_decrypt(
    base64_decode($encrypted),
    'AES-256-CBC',
    $key,
    OPENSSL_RAW_DATA,
    $iv
);

if ($decrypted === false) {
    while ($msg = openssl_error_string()) {
        logger()->error('OpenSSL Error: ' . $msg);
    }
}
```

2. **Verify OpenSSL version:**
```bash
php -i | grep OpenSSL
```

3. **Test OpenSSL directly:**
```php
$test = openssl_encrypt('test', 'AES-256-CBC', 'key', OPENSSL_RAW_DATA, random_bytes(16));
if ($test === false) {
    echo "OpenSSL is not working properly";
}
```

### Issue: Invalid Data Encoding

**Error:**
```
Exception: Invalid data encoding
```

**Solutions:**

1. **Verify data is base64:**
```php
if (base64_encode(base64_decode($data, true)) !== $data) {
    logger()->error('Data is not valid base64');
}
```

2. **Re-encrypt corrupted data:**
```php
// If you still have original data
$fresh = Crypto::encrypt($originalData);
```

3. **Check for data truncation:**
```php
// In database
$data = DB::table('users')->select('encrypted_field')->first();
logger()->info('Encrypted length: ' . strlen($data->encrypted_field));
```

### Issue: Facade Not Working

**Error:**
```
Call to undefined method Illuminate\Support\Facades\Crypto::encrypt()
```

**Solutions:**

1. **Use correct facade:**
```php
// Wrong
use Illuminate\Support\Facades\Crypto;

// Correct
use Akira\LaravelCrypto\Facades\Crypto;
```

2. **Check facade alias:**
```bash
php artisan optimize:clear
```

3. **Use full class name:**
```php
use Akira\LaravelCrypto\LaravelCrypto;

$crypto = app(LaravelCrypto::class);
$encrypted = $crypto->encrypt($data);
```

## Database Issues

### Issue: Cannot Query Encrypted Data

**Problem:** WHERE clause doesn't work on encrypted columns

**Explanation:** Encrypted data is randomized (due to IV), so direct queries won't work.

**Solutions:**

1. **Use hash indexing:**
```php
// Migration
Schema::table('users', function (Blueprint $table) {
    $table->text('ssn');
    $table->string('ssn_hash', 64)->index();
});

// Model
public function setSsnAttribute($value)
{
    $this->attributes['ssn'] = Crypto::encrypt($value);
    $this->attributes['ssn_hash'] = hash('sha256', $value);
}

// Query
$user = User::where('ssn_hash', hash('sha256', $searchSSN))->first();
```

2. **Use application-level filtering:**
```php
$users = User::all()->filter(function ($user) use ($searchValue) {
    return Crypto::decrypt($user->encrypted_field) === $searchValue;
});
```

### Issue: Slow Queries on Encrypted Data

**Problem:** Full table scans when searching encrypted data

**Solutions:**

1. **Index hash columns:**
```php
$table->string('field_hash')->index();
```

2. **Use separate searchable fields:**
```php
// Store last 4 digits unencrypted for search
$table->string('card_last_four', 4)->index();
$table->text('card_encrypted');
```

3. **Implement search tokens:**
```php
// Generate searchable tokens
$tokens = str_split(strtolower($ssn), 3);
foreach ($tokens as $token) {
    DB::table('search_tokens')->insert([
        'record_id' => $user->id,
        'token' => hash('sha256', $token),
    ]);
}
```

## Testing Issues

### Issue: Tests Failing with Encryption

**Error:**
```
Tests fail when using encryption
```

**Solutions:**

1. **Set test key in phpunit.xml:**
```xml
<phpunit>
    <php>
        <env name="CRYPTO_ENCRYPTION_KEY" value="test-key-for-testing-purposes-only-min-64-chars"/>
    </php>
</phpunit>
```

2. **Use consistent key in tests:**
```php
protected function setUp(): void
{
    parent::setUp();
    
    config([
        'crypto.encryption_key' => 'test-key-min-64-characters-long-for-testing',
    ]);
}
```

3. **Mock encryption in tests:**
```php
use Akira\LaravelCrypto\Facades\Crypto;

Crypto::shouldReceive('encrypt')
    ->andReturn('mocked-encrypted-value');

Crypto::shouldReceive('decrypt')
    ->andReturn('mocked-decrypted-value');
```

## Production Issues

### Issue: Key Rotation Failed

**Problem:** Data cannot be decrypted after key rotation

**Solution:**

1. **Keep old key temporarily:**
```php
class DualKeyDecryption
{
    public function decrypt(string $encrypted): string|false
    {
        // Try new key first
        $decrypted = Crypto::setKey(env('CRYPTO_ENCRYPTION_KEY'))
            ->decrypt($encrypted);
        
        if ($decrypted !== false) {
            return $decrypted;
        }
        
        // Fall back to old key
        $decrypted = Crypto::setKey(env('CRYPTO_OLD_KEY'))
            ->decrypt($encrypted);
        
        if ($decrypted !== false) {
            // Re-encrypt with new key
            $reencrypted = Crypto::setKey(env('CRYPTO_ENCRYPTION_KEY'))
                ->encrypt($decrypted);
            
            // Update in database
            // ... update logic ...
            
            return $decrypted;
        }
        
        return false;
    }
}
```

2. **Implement gradual rotation:**
```bash
php artisan crypto:rotate --verify-first
```

### Issue: Production Performance Degradation

**Solutions:**

1. **Enable OpCache:**
```ini
; php.ini
opcache.enable=1
opcache.memory_consumption=256
opcache.max_accelerated_files=20000
```

2. **Use Redis for caching:**
```env
CACHE_DRIVER=redis
```

3. **Monitor performance:**
```php
use Illuminate\Support\Facades\Log;

$start = microtime(true);
$encrypted = Crypto::encrypt($data);
$duration = microtime(true) - $start;

if ($duration > 0.1) { // 100ms threshold
    Log::warning('Slow encryption detected', [
        'duration' => $duration,
        'data_size' => strlen($data),
    ]);
}
```

## Getting Help

### Debugging Checklist

1. ✅ OpenSSL extension installed and enabled
2. ✅ Encryption key is properly set (minimum 64 characters)
3. ✅ Configuration file is published and loaded
4. ✅ Service provider is registered
5. ✅ Correct algorithm specified
6. ✅ Database columns are TEXT type
7. ✅ Using correct facade namespace
8. ✅ Cache cleared after configuration changes

### Enable Debugging

```php
// Add to .env
APP_DEBUG=true
LOG_LEVEL=debug

// Test encryption/decryption
try {
    logger()->debug('Testing encryption');
    $encrypted = Crypto::encrypt('test');
    logger()->debug('Encrypted: ' . $encrypted);
    
    $decrypted = Crypto::decrypt($encrypted);
    logger()->debug('Decrypted: ' . $decrypted);
    
    logger()->debug('Key: ' . substr(Crypto::getKey(), 0, 10) . '...');
    logger()->debug('Algorithm: ' . Crypto::getAlgorithm());
} catch (\Exception $e) {
    logger()->error('Crypto error: ' . $e->getMessage());
    logger()->error($e->getTraceAsString());
}
```

### Report Issues

If you can't resolve the issue:

1. Check existing issues: [GitHub Issues](https://github.com/akira/laravel-crypto/issues)
2. Create a new issue with:
   - Laravel version
   - PHP version
   - Package version
   - Error message
   - Steps to reproduce
   - Relevant code snippets

## Next Steps

- [Review security best practices](08-security-best-practices.md)
- [Check FAQ](10-faq.md)
- [API Reference](07-api-reference.md)
