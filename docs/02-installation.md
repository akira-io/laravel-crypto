# Installation

## Requirements

Before installing Laravel Crypto, ensure your system meets these requirements:

- **PHP**: 8.2 or higher
- **Laravel**: 11.x or 12.x
- **PHP Extensions**: 
  - OpenSSL (required)
  - Mbstring (recommended)

## Step-by-Step Installation

### Step 1: Install via Composer

Install the package using Composer:

```bash
composer require akira/laravel-crypto
```

### Step 2: Publish Configuration (Optional)

Publish the configuration file to customize settings:

```bash
php artisan vendor:publish --tag="crypto-config"
```

This creates `config/crypto.php` in your Laravel application.

### Step 3: Generate Encryption Key

Generate a secure encryption key:

```bash
php artisan crypto:generate-key
```

This will output:

```
Your encryption key is:
[generated-base64-encoded-key]

You can update your .env file with the following line:
CRYPTO_ENCRYPTION_KEY=[generated-base64-encoded-key]
```

### Step 4: Configure Environment

Add the generated key to your `.env` file:

```env
CRYPTO_ENCRYPTION_KEY=your-generated-key-here
```

### Step 5: Verify Installation

Create a simple test route to verify installation:

```php
use Akira\LaravelCrypto\Facades\Crypto;

Route::get('/test-crypto', function () {
    $original = 'Hello, Laravel Crypto!';
    $encrypted = Crypto::encrypt($original);
    $decrypted = Crypto::decrypt($encrypted);
    
    return [
        'original' => $original,
        'encrypted' => $encrypted,
        'decrypted' => $decrypted,
        'success' => $original === $decrypted
    ];
});
```

Visit `/test-crypto` in your browser. You should see:

```json
{
    "original": "Hello, Laravel Crypto!",
    "encrypted": "[base64-encoded-string]",
    "decrypted": "Hello, Laravel Crypto!",
    "success": true
}
```

## Installation in Different Environments

### Development Environment

```bash
# Install with dev dependencies
composer require akira/laravel-crypto --dev

# Generate a test key
php artisan crypto:generate-key --length=64
```

### Production Environment

```bash
# Install for production
composer require akira/laravel-crypto --no-dev --optimize-autoloader

# Generate a production key (store securely!)
php artisan crypto:generate-key --length=64

# Add to .env securely
# Never commit .env to version control!
```

### Docker Environment

Add to your `Dockerfile`:

```dockerfile
# Install PHP extensions
RUN docker-php-ext-install openssl

# Install Laravel Crypto
RUN composer require akira/laravel-crypto
```

In your `docker-compose.yml`:

```yaml
environment:
  - CRYPTO_ENCRYPTION_KEY=${CRYPTO_ENCRYPTION_KEY}
```

### Laravel Sail

```bash
# Install package
sail composer require akira/laravel-crypto

# Generate key
sail artisan crypto:generate-key

# Add to .env
```

## Package Auto-Discovery

Laravel Crypto supports Laravel's package auto-discovery. The service provider and facade are automatically registered.

If you need to manually register them (rare), add to `config/app.php`:

```php
'providers' => [
    // ...
    Akira\LaravelCrypto\LaravelCryptoServiceProvider::class,
],

'aliases' => [
    // ...
    'Crypto' => Akira\LaravelCrypto\Facades\Crypto::class,
],
```

## Verifying OpenSSL Extension

Ensure OpenSSL is installed:

```bash
php -m | grep openssl
```

If not installed:

### Ubuntu/Debian
```bash
sudo apt-get install php-openssl
```

### macOS (Homebrew)
```bash
brew install openssl
```

### Windows
Uncomment in `php.ini`:
```ini
extension=openssl
```

## Troubleshooting Installation

### Issue: "Class 'OpenSSL' not found"

**Solution**: Install the OpenSSL PHP extension:

```bash
# Ubuntu/Debian
sudo apt-get install php8.2-openssl

# Restart your web server
sudo service apache2 restart
# or
sudo service php8.2-fpm restart
```

### Issue: "Package not found"

**Solution**: Ensure you have access to Packagist:

```bash
composer clear-cache
composer require akira/laravel-crypto
```

### Issue: "Encryption key too short"

**Solution**: Generate a proper length key:

```bash
php artisan crypto:generate-key --length=64
```

### Issue: "Invalid data encoding"

**Solution**: Ensure your encryption key is properly base64 encoded in `.env`.

## Updating the Package

To update to the latest version:

```bash
composer update akira/laravel-crypto
```

To update to a specific version:

```bash
composer require akira/laravel-crypto:^1.0
```

## Uninstalling

To remove Laravel Crypto:

```bash
# Remove the package
composer remove akira/laravel-crypto

# Remove configuration file (optional)
rm config/crypto.php

# Remove from .env
# Remove CRYPTO_ENCRYPTION_KEY and related variables
```

## Next Steps

- [Configure Laravel Crypto](03-configuration.md)
- [Learn basic usage](04-basic-usage.md)
- [Explore examples](06-examples.md)
