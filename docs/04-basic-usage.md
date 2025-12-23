# Basic Usage

## Quick Start

### Using the Facade

The simplest way to use Laravel Crypto is through the `Crypto` facade:

```php
use Akira\LaravelCrypto\Facades\Crypto;

// Encrypt data
$encrypted = Crypto::encrypt('Hello World');

// Decrypt data
$decrypted = Crypto::decrypt($encrypted);
```

### Using Dependency Injection

You can also inject the service into your classes:

```php
use Akira\LaravelCrypto\LaravelCrypto;

class UserService
{
    public function __construct(
        private LaravelCrypto $crypto
    ) {}
    
    public function encryptUserData(string $data): string
    {
        return $this->crypto->encrypt($data);
    }
    
    public function decryptUserData(string $encrypted): string|false
    {
        return $this->crypto->decrypt($encrypted);
    }
}
```

### Using the Helper Function (Recommended)

The helper function provides full IDE autocomplete support:

```php
// Simple and clean with autocomplete
$encrypted = crypto()->encrypt('sensitive data');
$decrypted = crypto()->decrypt($encrypted);

// IDE will autocomplete all methods
$key = crypto()->getKey();
$algorithm = crypto()->getAlgorithm();
```

**Why use the helper?**
- ✅ Full IDE autocomplete support
- ✅ Clean and readable syntax
- ✅ No need to import facade or class
- ✅ Type hints work perfectly

### Using the Container (Alternative)

```php
$crypto = app(\Akira\LaravelCrypto\LaravelCrypto::class);

$encrypted = $crypto->encrypt('sensitive data');
$decrypted = $crypto->decrypt($encrypted);
```

## Basic Encryption

### Encrypt a String

```php
use Akira\LaravelCrypto\Facades\Crypto;

$plaintext = 'This is sensitive information';
$encrypted = Crypto::encrypt($plaintext);

// Output: Base64-encoded encrypted string
echo $encrypted; // "YjNsK3RzaGFsZGY4N3NkZmhzZGZo..."
```

### Encrypt Numeric Data

```php
$userId = 12345;
$encryptedId = Crypto::encrypt((string) $userId);
```

### Encrypt Boolean Values

```php
$isActive = true;
$encrypted = Crypto::encrypt($isActive ? '1' : '0');

// Later, decrypt and convert back
$decrypted = Crypto::decrypt($encrypted);
$isActive = $decrypted === '1';
```

### Encrypt Arrays (as JSON)

```php
$data = [
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'ssn' => '123-45-6789'
];

$encrypted = Crypto::encrypt(json_encode($data));

// Decrypt and decode
$decrypted = Crypto::decrypt($encrypted);
$originalData = json_decode($decrypted, true);
```

### Encrypt Objects (as JSON)

```php
$user = new stdClass();
$user->name = 'Jane Doe';
$user->email = 'jane@example.com';

$encrypted = Crypto::encrypt(json_encode($user));

// Decrypt and decode
$decrypted = Crypto::decrypt($encrypted);
$originalUser = json_decode($decrypted);
```

## Basic Decryption

### Decrypt a String

```php
use Akira\LaravelCrypto\Facades\Crypto;

$encrypted = 'YjNsK3RzaGFsZGY4N3NkZmhzZGZo...';
$decrypted = Crypto::decrypt($encrypted);

if ($decrypted === false) {
    // Decryption failed
    echo "Failed to decrypt data";
} else {
    echo $decrypted; // "This is sensitive information"
}
```

### Safe Decryption with Error Handling

```php
use Akira\LaravelCrypto\Facades\Crypto;

function safeDecrypt(string $encrypted): ?string
{
    $decrypted = Crypto::decrypt($encrypted);
    
    if ($decrypted === false) {
        logger()->error('Decryption failed', ['data' => $encrypted]);
        return null;
    }
    
    return $decrypted;
}

$result = safeDecrypt($encryptedData);
if ($result !== null) {
    // Use decrypted data
}
```

## Working with Different Data Types

### Strings

```php
$text = "Hello, World!";
$encrypted = Crypto::encrypt($text);
$decrypted = Crypto::decrypt($encrypted);

assert($text === $decrypted); // true
```

### Integers

```php
$number = 42;
$encrypted = Crypto::encrypt((string) $number);
$decrypted = (int) Crypto::decrypt($encrypted);

assert($number === $decrypted); // true
```

### Floats

```php
$price = 99.99;
$encrypted = Crypto::encrypt((string) $price);
$decrypted = (float) Crypto::decrypt($encrypted);

assert($price === $decrypted); // true
```

### JSON Data

```php
$data = ['key' => 'value', 'number' => 123];
$encrypted = Crypto::encrypt(json_encode($data));
$decrypted = json_decode(Crypto::decrypt($encrypted), true);

assert($data === $decrypted); // true
```

### Binary Data

```php
$binaryData = random_bytes(32);
$encrypted = Crypto::encrypt(base64_encode($binaryData));
$decrypted = base64_decode(Crypto::decrypt($encrypted));

assert($binaryData === $decrypted); // true
```

## Method Chaining

```php
use Akira\LaravelCrypto\Facades\Crypto;

// Change key and encrypt
$encrypted = Crypto::setKey('custom-key-here')
    ->encrypt('data');

// Change algorithm and encrypt
$encrypted = Crypto::setAlgorithm('AES-128-CBC')
    ->encrypt('data');

// Chain multiple operations
$encrypted = Crypto::setKey('custom-key')
    ->setAlgorithm('AES-256-CBC')
    ->encrypt('sensitive data');
```

## Static Method Access

```php
use Akira\LaravelCrypto\LaravelCrypto;

// Create instance using static method
$crypto = LaravelCrypto::make();

$encrypted = $crypto->encrypt('data');
$decrypted = $crypto->decrypt($encrypted);
```

## Common Patterns

### Encrypt Before Saving

```php
use Akira\LaravelCrypto\Facades\Crypto;
use App\Models\User;

$user = new User();
$user->name = 'John Doe';
$user->ssn = Crypto::encrypt('123-45-6789');
$user->save();
```

### Decrypt After Retrieving

```php
use Akira\LaravelCrypto\Facades\Crypto;
use App\Models\User;

$user = User::find(1);
$ssn = Crypto::decrypt($user->ssn);

echo "SSN: {$ssn}";
```

### Encrypt in Eloquent Accessor

```php
use Akira\LaravelCrypto\Facades\Crypto;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;

class User extends Model
{
    protected function ssn(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => Crypto::decrypt($value),
            set: fn ($value) => Crypto::encrypt($value),
        );
    }
}

// Usage
$user = new User();
$user->ssn = '123-45-6789';  // Automatically encrypted
$user->save();

$ssn = $user->ssn;  // Automatically decrypted
```

### Encrypt in Form Request

```php
use Akira\LaravelCrypto\Facades\Crypto;
use Illuminate\Foundation\Http\FormRequest;

class StoreUserRequest extends FormRequest
{
    protected function prepareForValidation()
    {
        $this->merge([
            'ssn' => Crypto::encrypt($this->ssn),
        ]);
    }
}
```

### Encrypt API Responses

```php
use Akira\LaravelCrypto\Facades\Crypto;

Route::get('/api/sensitive-data', function () {
    $data = ['secret' => 'classified information'];
    
    return response()->json([
        'data' => Crypto::encrypt(json_encode($data))
    ]);
});
```

## Error Handling

### Basic Error Handling

```php
use Akira\LaravelCrypto\Facades\Crypto;

$encrypted = 'potentially-invalid-data';

try {
    $decrypted = Crypto::decrypt($encrypted);
    
    if ($decrypted === false) {
        // Handle decryption failure
        return response()->json(['error' => 'Decryption failed'], 400);
    }
    
    return response()->json(['data' => $decrypted]);
} catch (\Exception $e) {
    logger()->error('Crypto error: ' . $e->getMessage());
    return response()->json(['error' => 'Server error'], 500);
}
```

### Graceful Fallback

```php
use Akira\LaravelCrypto\Facades\Crypto;

function getDecryptedValue(string $encrypted, string $default = ''): string
{
    $decrypted = Crypto::decrypt($encrypted);
    return $decrypted !== false ? $decrypted : $default;
}

$value = getDecryptedValue($encryptedData, 'default-value');
```

## Validation

### Validate Encrypted Data

```php
use Akira\LaravelCrypto\Facades\Crypto;

function isValidEncrypted(string $data): bool
{
    // Try to decrypt
    $result = Crypto::decrypt($data);
    return $result !== false;
}

if (isValidEncrypted($input)) {
    // Process valid encrypted data
}
```

### Custom Validation Rule

```php
use Akira\LaravelCrypto\Facades\Crypto;
use Illuminate\Contracts\Validation\Rule;

class EncryptedData implements Rule
{
    public function passes($attribute, $value)
    {
        return Crypto::decrypt($value) !== false;
    }
    
    public function message()
    {
        return 'The :attribute must be valid encrypted data.';
    }
}

// Usage in validation
$request->validate([
    'encrypted_field' => ['required', new EncryptedData],
]);
```

## Best Practices

### 1. Always Handle Decryption Failures

```php
$decrypted = Crypto::decrypt($encrypted);

if ($decrypted === false) {
    // Handle error appropriately
    logger()->error('Decryption failed');
    return null;
}

return $decrypted;
```

### 2. Encrypt Sensitive Data Only

Don't encrypt everything - only sensitive data:
- ✅ SSN, credit cards, passwords
- ✅ Personal information
- ✅ API tokens
- ❌ Public data
- ❌ Non-sensitive IDs

### 3. Store Encrypted Data in Appropriate Column Types

```php
// Migration
Schema::create('users', function (Blueprint $table) {
    $table->id();
    $table->text('ssn');  // Use TEXT for encrypted data
    $table->timestamps();
});
```

### 4. Use Type Casting

```php
// Always cast to string when encrypting
$encrypted = Crypto::encrypt((string) $value);

// Cast back to original type when decrypting
$intValue = (int) Crypto::decrypt($encrypted);
```

### 5. Don't Re-encrypt Encrypted Data

```php
// Check if already encrypted
function encryptIfNeeded($value) {
    if (Crypto::decrypt($value) !== false) {
        return $value; // Already encrypted
    }
    return Crypto::encrypt($value);
}
```

## Next Steps

- [Explore advanced usage](05-advanced-usage.md)
- [See practical examples](06-examples.md)
- [Learn about security best practices](08-security-best-practices.md)

**Previous:** [Configuration](03-configuration.md) | **Next:** [Advanced Usage](05-advanced-usage.md)
