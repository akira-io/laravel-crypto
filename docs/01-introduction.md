# Introduction

## What is Laravel Crypto?

**Laravel Crypto** is a comprehensive Laravel package designed to simplify secure data encryption and decryption in your Laravel applications. It provides a robust, easy-to-use interface for encrypting sensitive data using modern cryptographic algorithms.

## Why Laravel Crypto?

While Laravel includes built-in encryption capabilities, Laravel Crypto offers:

### 🔐 Enhanced Security
- **PBKDF2 Key Derivation**: Derives encryption keys using PBKDF2 (Password-Based Key Derivation Function 2) with configurable iterations
- **Randomized IVs**: Every encryption operation uses a unique, randomly generated initialization vector
- **Separate Encryption Keys**: Use different keys from your application key for specific encryption needs

### ⚙️ Flexibility
- **Multiple Algorithm Support**: Configure different encryption algorithms (AES-256-CBC, AES-128-CBC, etc.)
- **Customizable Parameters**: Fine-tune key sizes, iteration counts, and algorithm choices
- **Runtime Configuration**: Change encryption settings on-the-fly

### 🚀 Developer-Friendly
- **Facade Support**: Simple, expressive syntax using Laravel facades
- **Service Container Integration**: Fully integrated with Laravel's dependency injection
- **Artisan Commands**: Generate secure keys directly from the command line
- **Well-Documented**: Comprehensive documentation with real-world examples

## Use Cases

Laravel Crypto is perfect for:

- **Sensitive User Data**: Encrypt personal information like SSN, credit card numbers, addresses
- **API Tokens**: Securely store third-party API tokens and credentials
- **File Encryption**: Encrypt files before storing them
- **Database Fields**: Encrypt sensitive database columns
- **Inter-Service Communication**: Encrypt data transmitted between microservices
- **Audit Logs**: Protect sensitive information in audit trails
- **Backup Data**: Encrypt sensitive data before backing up
- **Compliance Requirements**: Meet GDPR, HIPAA, or other regulatory requirements

## How It Works

Laravel Crypto uses a multi-layered approach to encryption:

1. **Key Derivation**: Your encryption key is processed through PBKDF2 with a salt and configurable iterations
2. **IV Generation**: A random initialization vector (IV) is generated for each encryption
3. **Encryption**: Data is encrypted using OpenSSL with the derived key and IV
4. **Encoding**: The IV and encrypted data are concatenated and base64-encoded for storage
5. **Decryption**: The process is reversed, extracting the IV and decrypting the data

```
Plain Text → PBKDF2 Key Derivation → Random IV → OpenSSL Encryption → Base64 Encoding → Encrypted String
```

## Security Features

### PBKDF2 Key Derivation
Laravel Crypto uses PBKDF2 (Password-Based Key Derivation Function 2) to derive the actual encryption key from your configured key. This adds an extra layer of security by:
- Making brute-force attacks more computationally expensive
- Using a salt to prevent rainbow table attacks
- Allowing configurable iteration counts (default: 10,000)

### Randomized Initialization Vectors (IVs)
Each encryption operation generates a unique IV using PHP's `random_bytes()` function, which:
- Ensures identical plaintexts produce different ciphertexts
- Prevents pattern analysis attacks
- Is cryptographically secure

### Binary-Safe Operations
All string operations use `mb_substr()` with `8bit` encoding to ensure binary safety when handling encrypted data.

## Architecture

Laravel Crypto consists of:

- **LaravelCrypto Class**: Core encryption/decryption logic
- **Crypto Facade**: Convenient facade for easy access
- **Service Provider**: Registers the service with Laravel's container
- **Configuration File**: Centralized configuration management
- **Artisan Command**: Key generation utility

## Performance Considerations

- **Key Derivation Overhead**: PBKDF2 iterations add computational cost (configurable)
- **IV Generation**: Minimal overhead from random byte generation
- **OpenSSL Performance**: Leverages native OpenSSL for fast encryption/decryption
- **Base64 Encoding**: Increases encrypted data size by ~33%

## Next Steps

- [Install Laravel Crypto](02-installation.md)
- [Configure the package](03-configuration.md)
- [Learn basic usage](04-basic-usage.md)
- [Explore advanced features](05-advanced-usage.md)
