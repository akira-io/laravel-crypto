# Roadmap

This roadmap outlines potential enhancements for Laravel Crypto based on the existing architecture and extension points. No dates or versions are specified—this is a feature-oriented plan reflecting future possibilities.

## Current Foundation

Laravel Crypto provides:

- **AES-256-CBC encryption** with OpenSSL
- **PBKDF2 key derivation** for enhanced security
- **Randomized initialization vectors (IVs)** for each encryption operation
- **Configurable algorithm, key size, and iterations**
- **Artisan command** for secure key generation
- **Helper function and Facade** for developer convenience

## Future Enhancements

### Multi-Algorithm Support

Currently, the algorithm can be changed via configuration, but the package assumes CBC mode behavior. Future work could:

- Add native support for **AEAD algorithms** (AES-GCM, ChaCha20-Poly1305)
- Implement automatic mode detection for proper handling of authentication tags
- Provide explicit encryption mode classes (e.g., `CbcMode`, `GcmMode`)

### Key Rotation

The package currently uses a single encryption key. Key rotation features could include:

- **Multi-key configuration** allowing fallback to previous keys for decryption
- **Automatic re-encryption** utilities to migrate data to new keys
- **Key versioning** to track which key encrypted each payload
- **Artisan commands** to rotate keys and re-encrypt existing data

### Encryption Profiles

Allow developers to define multiple encryption profiles for different use cases:

- **Default profile** for general application data
- **High-security profile** with increased PBKDF2 iterations
- **Performance profile** with optimized settings for high-throughput scenarios
- **Per-model or per-field profile selection**

### Database Column Encryption

Extend the package to support transparent database encryption:

- **Cast for encrypted attributes** on Eloquent models
- **Searchable encryption** using deterministic encryption where appropriate
- **JSON field encryption** for nested data structures
- **Migration helpers** to encrypt existing columns

### Framework Integration

Deeper Laravel integration could include:

- **Middleware** for automatic request/response encryption
- **Queue payload encryption** for sensitive job data
- **Cache value encryption** for secure temporary storage
- **Session encryption override** with custom crypto implementation

### Performance Optimization

Opportunities for performance improvements:

- **Caching derived keys** to avoid repeated PBKDF2 operations
- **Async encryption** for large batches of data
- **Streaming encryption** for file handling
- **Configurable iteration counts** based on environment (lower for testing)

### Developer Experience

Enhancements to make the package easier to use:

- **Validation rules** for encrypted data format
- **Testing helpers** to mock encryption in tests
- **Encryption debugging mode** with detailed logging
- **Benchmarking tools** to measure encryption performance

### Security Features

Additional security-focused features:

- **Automatic key expiry** and enforcement
- **Encryption metadata** (timestamp, algorithm, key ID)
- **Audit logging** for encryption operations
- **Security event broadcasting** for monitoring

### Interoperability

Cross-platform and legacy support:

- **Export/import** utilities for encrypted data
- **Compatibility modes** for other encryption libraries
- **Format converters** for migrating from Laravel's native encrypter
- **Standardized payload format** with version markers

## Extension Points

The current architecture provides several natural extension points:

### Custom Algorithms

The `LaravelCrypto` class accepts algorithm configuration. Developers can already:

- Specify custom OpenSSL algorithms via `CRYPTO_CIPHER`
- Adjust IV length for different algorithms
- Configure key sizes appropriately

### Middleware Integration

The service container binding makes it easy to:

- Inject `LaravelCrypto` into middleware
- Apply encryption at the HTTP layer
- Transform request/response data automatically

### Service Provider Customization

The service provider can be extended to:

- Register multiple crypto instances with different configurations
- Bind custom implementations
- Add package discovery for crypto plugins

### Configuration Extension

The configuration array structure supports:

- Additional algorithm-specific options
- Custom key derivation parameters
- Mode-specific settings (tag length for AEAD)

## Community Input

This roadmap is based on the current codebase architecture and common encryption use cases. The actual implementation of features will depend on:

- Community feedback and feature requests
- Security best practices evolution
- Laravel framework changes
- PHP and OpenSSL library updates

Contributions and suggestions are welcome through the GitHub repository.

**Next:** [Introduction](01-introduction.md)
