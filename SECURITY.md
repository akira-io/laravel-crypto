# Security Policy

## Supported Versions

We take security seriously and actively maintain the following versions:

| Version | Supported          |
| ------- | ------------------ |
| 1.x     | :white_check_mark: |
| < 1.0   | :x:                |

## Reporting a Vulnerability

**Please do not report security vulnerabilities through public GitHub issues.**

If you discover a security vulnerability within Laravel Crypto, please follow these steps:

### 1. Private Disclosure

Send an email to: **kidiatoliny@akira-io.com**

Include:

- **Type of vulnerability** (e.g., key exposure, encryption bypass, etc.)
- **Full description** of the vulnerability
- **Steps to reproduce** the issue
- **Potential impact** and severity assessment
- **Suggested fix** (if you have one)
- **Your contact information** for follow-up

### 2. Response Timeline

- **Initial Response**: Within 48 hours
- **Confirmation**: Within 7 days
- **Fix Timeline**: Depends on severity
  - Critical: 7 days
  - High: 14 days
  - Medium: 30 days
  - Low: 60 days

### 3. Responsible Disclosure

We follow coordinated vulnerability disclosure:

- We will acknowledge your email within 48 hours
- We will provide a detailed response within 7 days
- We will work with you to understand and fix the issue
- We will credit you in the security advisory (if you wish)
- We will not disclose the issue until a fix is released

### 4. Public Disclosure

After a fix is released:

- Security advisory will be published
- Credit given to the reporter (unless anonymous)
- Details published in CHANGELOG
- Notice sent to package users

## Security Best Practices

When using Laravel Crypto, follow these security practices:

### Key Management

- **Never commit encryption keys** to version control
- **Use environment variables** for all keys
- **Generate strong keys** using `php artisan crypto:generate-key`
- **Rotate keys regularly** in production environments
- **Use different keys** for different environments
- **Store backup keys** in secure offline storage

### Configuration

- **Use AES-256-CBC or AES-256-GCM** for production
- **Set iterations** to at least 10,000 (50,000+ for sensitive data)
- **Validate configuration** before deploying
- **Never use default or example keys**

### Code Practices

- **Validate decrypted data** before use
- **Handle decryption failures** gracefully
- **Log security events** appropriately (never log keys or sensitive data)
- **Use HTTPS** for transmitting encrypted data
- **Sanitize input** before encryption
- **Implement rate limiting** on encryption/decryption endpoints

### Deployment

- **Secure your .env file** (permissions 600)
- **Use secure key storage** services when available
- **Monitor for suspicious activity**
- **Keep dependencies updated**
- **Enable audit logging**

## Known Security Considerations

### Key Derivation

Laravel Crypto uses PBKDF2 for key derivation. The security depends on:

- **Iteration count**: Higher is more secure (default: 10,000)
- **Key length**: Use at least 64 characters
- **Salt**: Automatically handled by the package

### Initialization Vectors (IVs)

- IVs are randomly generated for each encryption
- IVs are prepended to encrypted data
- IVs are not secret but must be unique per encryption

### Algorithm Selection

- **AES-256-CBC**: Secure, widely supported (default)
- **AES-256-GCM**: Provides authentication, prevents tampering
- **AES-128-CBC**: Faster but less secure, use only if needed

### Data Size Considerations

- Encrypted data is ~33% larger due to base64 encoding
- IV adds 16 bytes to each encrypted value
- Consider compression before encryption for large data

### Performance vs Security

Balance based on your needs:

- **High security**: AES-256-GCM, 100,000 iterations
- **Balanced**: AES-256-CBC, 10,000 iterations (default)
- **Performance**: AES-128-CBC, 5,000 iterations (not recommended for sensitive data)

## Encryption Limitations

### What This Package Does NOT Protect Against

- **Compromised servers**: If your server is compromised, encryption keys may be exposed
- **Side-channel attacks**: Timing attacks or other sophisticated attacks
- **Quantum computing**: Future quantum computers may break current encryption
- **Social engineering**: User errors or manipulation
- **Insider threats**: Malicious users with system access

### Out of Scope

The following are explicitly out of scope for security reports:

- Issues requiring physical access to the server
- Social engineering attacks
- Attacks requiring compromised dependencies
- Theoretical attacks without proof of concept
- Issues in unsupported versions

## Security Updates

### Notification

To receive security notifications:

1. **Watch** this repository on GitHub
2. **Enable notifications** for security advisories
3. **Subscribe** to release notifications

### Update Policy

- **Critical patches**: Released immediately
- **High severity**: Released within 7-14 days
- **Medium severity**: Released with next minor version
- **Low severity**: Released with next major version

## Compliance

Laravel Crypto can help meet compliance requirements for:

- **GDPR**: Data encryption at rest
- **HIPAA**: Protected Health Information (PHI) encryption
- **PCI DSS**: Cardholder data encryption
- **SOC 2**: Encryption controls

**Note**: Using this package alone does not guarantee compliance. Consult with compliance experts for your specific requirements.

## Cryptography Audit

This package uses:

- **OpenSSL**: Industry-standard cryptographic library
- **PBKDF2**: NIST-approved key derivation
- **AES**: NIST-approved encryption algorithm

We recommend independent security audits for critical applications.

## Third-Party Dependencies

Security of dependencies:

- Monitor Composer dependencies with `composer audit`
- Update dependencies regularly
- Review security advisories for Laravel and PHP

## Bug Bounty

We currently do not offer a bug bounty program. However, we deeply appreciate responsible disclosure and will:

- Credit you in security advisories
- Acknowledge your contribution publicly (if desired)
- Respond promptly to all reports

## Contact

For security concerns:

- **Email**: kidiatoliny@akira-io.com
- **GitHub**: Open a security advisory (for confirmed issues)

For general questions:

- **GitHub Discussions**: Use for non-security questions
- **GitHub Issues**: Use for bugs (not security vulnerabilities)

## Additional Resources

- [OWASP Cryptographic Storage Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/Cryptographic_Storage_Cheat_Sheet.html)
- [NIST Encryption Standards](https://csrc.nist.gov/projects/cryptographic-standards-and-guidelines)
- [Laravel Security Best Practices](https://laravel.com/docs/security)

---

Thank you for helping keep Laravel Crypto and its users secure!
