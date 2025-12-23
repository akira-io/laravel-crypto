# Contributing to Laravel Crypto

Thank you for considering contributing to Laravel Crypto! This document outlines the guidelines and process for contributing to this project.

## Code of Conduct

This project adheres to the Laravel community standards. By participating, you are expected to uphold professional and respectful behavior.

## How Can I Contribute?

### Reporting Bugs

Before creating bug reports, please check the existing issues to avoid duplicates. When creating a bug report, include:

- **Clear title and description**
- **Steps to reproduce** the issue
- **Expected behavior** vs actual behavior
- **Laravel version** and PHP version
- **Package version**
- **Code samples** (if applicable)
- **Error messages** and stack traces

Use the issue template when available.

### Suggesting Enhancements

Enhancement suggestions are welcome! When suggesting an enhancement:

- **Use a clear title** describing the enhancement
- **Provide detailed description** of the proposed functionality
- **Explain why this enhancement** would be useful
- **Include code examples** if applicable

### Pull Requests

1. **Fork the repository** and create your branch from `main`
2. **Follow coding standards** (see below)
3. **Write tests** for your changes
4. **Ensure tests pass** before submitting
5. **Update documentation** if needed
6. **Write clear commit messages** following conventional commits
7. **Submit the pull request** with a clear description

## Development Setup

### Prerequisites

- PHP 8.2 or higher
- Composer
- OpenSSL extension

### Installation

```bash
# Clone your fork
git clone https://github.com/YOUR_USERNAME/laravel-crypto.git
cd laravel-crypto

# Install dependencies
composer install

# Install dev dependencies
composer install --dev
```

### Running Tests

```bash
# Run all tests
composer test

# Run specific test suite
./vendor/bin/pest

# Run with coverage
./vendor/bin/pest --coverage
```

## Coding Standards

This project follows Laravel and PSR-12 coding standards.

### Code Style

We use Laravel Pint for code formatting:

```bash
# Check code style
composer test:lint

# Fix code style automatically
composer lint
```

### Static Analysis

We use PHPStan for static analysis:

```bash
# Run static analysis
composer test:types
```

### Code Quality

```bash
# Run refactor checks
composer test:refactor

# Apply refactoring
composer refactor
```

## Commit Messages

Follow [Conventional Commits](https://www.conventionalcommits.org/) specification:

```
feat: add support for AES-GCM encryption
fix: resolve key derivation issue with special characters
docs: update configuration examples
test: add tests for key rotation
refactor: simplify encryption logic
perf: optimize key derivation performance
```

### Commit Types

- `feat`: New feature
- `fix`: Bug fix
- `docs`: Documentation changes
- `test`: Adding or updating tests
- `refactor`: Code refactoring
- `perf`: Performance improvements
- `chore`: Maintenance tasks
- `ci`: CI/CD changes

## Testing Guidelines

### Writing Tests

- Use **Pest PHP** testing framework
- Write **descriptive test names**
- Test **both success and failure cases**
- Include **edge cases**
- Mock **external dependencies**

Example:

```php
test('it encrypts and decrypts data successfully', function () {
    $data = 'sensitive information';
    $encrypted = crypto()->encrypt($data);
    $decrypted = crypto()->decrypt($encrypted);
    
    expect($decrypted)->toBe($data);
});

test('it handles empty string encryption', function () {
    $encrypted = crypto()->encrypt('');
    $decrypted = crypto()->decrypt($encrypted);
    
    expect($decrypted)->toBe('');
});

test('it returns false on invalid encrypted data', function () {
    $result = crypto()->decrypt('invalid-data');
    
    expect($result)->toBeFalse();
});
```

### Test Coverage

- Aim for **80%+ code coverage**
- Cover all **public methods**
- Test **error conditions**
- Test **boundary cases**

## Documentation

### Code Documentation

- Add **PHPDoc blocks** for all public methods
- Include **@param** and **@return** annotations
- Document **exceptions** that can be thrown
- Add **usage examples** in docblocks

Example:

```php
/**
 * Encrypt the given data using the configured algorithm.
 *
 * @param string $data The plain text data to encrypt
 * @return string The base64-encoded encrypted data
 * @throws \Exception If the encryption key is too short
 *
 * @example
 * $encrypted = $crypto->encrypt('sensitive data');
 */
public function encrypt(string $data): string
{
    // implementation
}
```

### User Documentation

When adding features:

- Update relevant documentation in `/docs`
- Add examples to demonstrate usage
- Update the API reference if needed
- Include security considerations

## Security

Do not open public issues for security vulnerabilities. See [SECURITY.md](SECURITY.md) for reporting procedures.

## Pull Request Process

1. **Update documentation** for any changed functionality
2. **Add tests** for new features
3. **Ensure all tests pass**
4. **Run code quality checks**
5. **Update CHANGELOG.md** following Keep a Changelog format
6. **Request review** from maintainers

### PR Checklist

- [ ] Code follows project style guidelines
- [ ] Tests added/updated and passing
- [ ] Documentation updated
- [ ] CHANGELOG.md updated
- [ ] No breaking changes (or clearly documented)
- [ ] Commit messages follow conventional commits

## Branch Naming

Use descriptive branch names:

- `feat/add-gcm-support`
- `fix/key-derivation-bug`
- `docs/improve-installation-guide`
- `test/add-integration-tests`

## Review Process

- Maintainers will review PRs as soon as possible
- Address feedback promptly
- Keep PRs focused and reasonably sized
- Be open to suggestions and constructive criticism

## Release Process

Maintainers handle releases following semantic versioning:

- **MAJOR**: Breaking changes
- **MINOR**: New features (backward compatible)
- **PATCH**: Bug fixes (backward compatible)

## Getting Help

- Check the [documentation](docs/)
- Search existing issues
- Ask questions in discussions
- Join community channels

## Recognition

Contributors will be recognized in:

- Repository contributors list
- Release notes
- Project README

## License

By contributing, you agree that your contributions will be licensed under the MIT License.

---

Thank you for contributing to Laravel Crypto! Your efforts help make secure encryption accessible to the Laravel community.
