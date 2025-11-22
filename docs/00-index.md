# Laravel Crypto - Complete Documentation

Welcome to the complete documentation for **Laravel Crypto**, a powerful Laravel package for secure data encryption and decryption.

## Table of Contents

1. [Introduction](01-introduction.md)
2. [Installation](02-installation.md)
3. [Configuration](03-configuration.md)
4. [Basic Usage](04-basic-usage.md)
5. [Advanced Usage](05-advanced-usage.md)
6. [Examples](06-examples.md)
7. [API Reference](07-api-reference.md)
8. [Security Best Practices](08-security-best-practices.md)
9. [Troubleshooting](09-troubleshooting.md)
10. [FAQ](10-faq.md)
11. [Helper Function Usage](11-helper-usage.md) ⭐ **Recommended**

## Quick Links

- **GitHub Repository**: [https://github.com/akira/laravel-crypto](https://github.com/akira/laravel-crypto)
- **Packagist**: [https://packagist.org/packages/akira/laravel-crypto](https://packagist.org/packages/akira/laravel-crypto)
- **Issues**: [Report a bug or request a feature](https://github.com/akira/laravel-crypto/issues)

## Quick Start

### Installation

```bash
composer require akira/laravel-crypto
```

### Publish Configuration

```bash
php artisan vendor:publish --tag="crypto-config"
```

### Generate Encryption Key

```bash
php artisan crypto:generate-key
```

### Basic Encryption

```php
// Using helper function (recommended - has IDE autocomplete)
$encrypted = crypto()->encrypt('Hello World');
$decrypted = crypto()->decrypt($encrypted);

// Or using facade
use Akira\LaravelCrypto\Facades\Crypto;
$encrypted = Crypto::encrypt('Hello World');
```

## Features

- ✅ **Secure AES-256-CBC Encryption** - Industry-standard encryption algorithm
- ✅ **Randomized IVs** - Each encryption uses a unique initialization vector
- ✅ **Key Derivation** - PBKDF2 key derivation for enhanced security
- ✅ **Customizable Configuration** - Full control over algorithms, key sizes, and iterations
- ✅ **Laravel Integration** - Seamless integration with Laravel's service container
- ✅ **Facade Support** - Easy-to-use facade for quick access
- ✅ **Artisan Commands** - Generate secure encryption keys with artisan
- ✅ **Comprehensive Tests** - Fully tested with Pest PHP

## System Requirements

- PHP 8.2 or higher
- Laravel 11.x or 12.x
- OpenSSL PHP Extension

## License

Laravel Crypto is open-source software licensed under the [MIT license](../LICENSE.md).
