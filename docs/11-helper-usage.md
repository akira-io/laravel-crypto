# Helper Function Usage

## Overview

Laravel Crypto provides a convenient `crypto()` helper function that gives you full IDE autocomplete support, making development easier and more productive.

## Why Use the Helper?

### ✅ Benefits

1. **Full IDE Autocomplete** - Your IDE will suggest all available methods
2. **Clean Syntax** - No need to import facades or classes
3. **Type Safety** - Full type hints and return types
4. **Consistency** - Works like Laravel's built-in helpers (`app()`, `config()`, etc.)

### ❌ Facade Limitations

The facade doesn't provide IDE autocomplete:

```php
use Akira\LaravelCrypto\Facades\Crypto;

// No autocomplete - you have to remember method names
Crypto::encrypt('data');
```

### ✅ Helper Solution

```php
// Full autocomplete - IDE suggests encrypt(), decrypt(), getKey(), etc.
crypto()->encrypt('data');
```

## Basic Usage

### Encryption

```php
// Simple encryption
$encrypted = crypto()->encrypt('Hello World');

// Encrypt with JSON
$data = ['name' => 'John', 'email' => 'john@example.com'];
$encrypted = crypto()->encrypt(json_encode($data));
```

### Decryption

```php
// Simple decryption
$decrypted = crypto()->decrypt($encrypted);

// Decrypt with error handling
$decrypted = crypto()->decrypt($encrypted);
if ($decrypted === false) {
    logger()->error('Decryption failed');
}
```

### Method Chaining

```php
// Chain methods
$encrypted = crypto()
    ->setAlgorithm('AES-128-CBC')
    ->encrypt('data');

// Get configuration
$key = crypto()->getKey();
$algorithm = crypto()->getAlgorithm();
```

## In Controllers

```php
namespace App\Http\Controllers;

use Illuminate\Http\Request;

class UserController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required',
            'ssn' => 'required',
        ]);
        
        User::create([
            'name' => $validated['name'],
            'ssn' => crypto()->encrypt($validated['ssn']),
        ]);
        
        return response()->json(['message' => 'User created']);
    }
    
    public function show(User $user)
    {
        $ssn = crypto()->decrypt($user->ssn);
        
        return response()->json([
            'name' => $user->name,
            'ssn_masked' => 'XXX-XX-' . substr($ssn, -4),
        ]);
    }
}
```

## In Models

### Using Accessors/Mutators

```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    public function setSsnAttribute($value)
    {
        $this->attributes['ssn'] = crypto()->encrypt($value);
    }
    
    public function getSsnAttribute($value)
    {
        $decrypted = crypto()->decrypt($value);
        return $decrypted !== false ? $decrypted : null;
    }
}

// Usage
$user = new User();
$user->ssn = '123-45-6789';  // Automatically encrypted
$user->save();

echo $user->ssn;  // Automatically decrypted
```

### Using Attribute Casting

```php
namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    protected function ssn(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => crypto()->decrypt($value) ?: null,
            set: fn ($value) => crypto()->encrypt($value),
        );
    }
}
```

## In Services

```php
namespace App\Services;

class PaymentService
{
    public function storeCard(array $cardData): void
    {
        $encrypted = crypto()->encrypt(json_encode([
            'number' => $cardData['number'],
            'exp_month' => $cardData['exp_month'],
            'exp_year' => $cardData['exp_year'],
        ]));
        
        DB::table('payment_methods')->insert([
            'user_id' => auth()->id(),
            'card_data' => $encrypted,
            'last_four' => substr($cardData['number'], -4),
        ]);
    }
    
    public function getCard(int $id): ?array
    {
        $record = DB::table('payment_methods')->find($id);
        
        if (!$record) {
            return null;
        }
        
        $decrypted = crypto()->decrypt($record->card_data);
        
        return $decrypted !== false 
            ? json_decode($decrypted, true) 
            : null;
    }
}
```

## In Jobs

```php
namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

class ProcessSensitiveData implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;
    
    public function __construct(
        public string $encryptedData
    ) {}
    
    public static function dispatch(array $data): void
    {
        $encrypted = crypto()->encrypt(json_encode($data));
        parent::dispatch($encrypted);
    }
    
    public function handle(): void
    {
        $decrypted = crypto()->decrypt($this->encryptedData);
        
        if ($decrypted === false) {
            $this->fail('Failed to decrypt data');
            return;
        }
        
        $data = json_decode($decrypted, true);
        
        // Process data
        $this->process($data);
    }
    
    private function process(array $data): void
    {
        // Your processing logic
    }
}

// Usage
ProcessSensitiveData::dispatch([
    'ssn' => '123-45-6789',
    'credit_card' => '4111-1111-1111-1111',
]);
```

## In Middleware

```php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EncryptSensitiveRequest
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->has('ssn')) {
            $request->merge([
                'ssn' => crypto()->encrypt($request->input('ssn'))
            ]);
        }
        
        if ($request->has('credit_card')) {
            $request->merge([
                'credit_card' => crypto()->encrypt($request->input('credit_card'))
            ]);
        }
        
        return $next($request);
    }
}
```

## In Requests

```php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreUserRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->has('ssn')) {
            $this->merge([
                'ssn_encrypted' => crypto()->encrypt($this->ssn)
            ]);
        }
    }
    
    public function rules(): array
    {
        return [
            'name' => 'required|string',
            'ssn' => 'required|string|regex:/^\d{3}-\d{2}-\d{4}$/',
        ];
    }
}
```

## In Blade Templates

```php
@php
    $decrypted = crypto()->decrypt($user->ssn);
    $masked = $decrypted !== false 
        ? 'XXX-XX-' . substr($decrypted, -4) 
        : 'N/A';
@endphp

<div>
    <strong>SSN:</strong> {{ $masked }}
</div>
```

## In API Resources

```php
namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray($request): array
    {
        $ssn = crypto()->decrypt($this->ssn);
        
        return [
            'id' => $this->id,
            'name' => $this->name,
            'ssn_masked' => $ssn !== false 
                ? 'XXX-XX-' . substr($ssn, -4) 
                : null,
        ];
    }
}
```

## In Console Commands

```php
namespace App\Console\Commands;

use Illuminate\Console\Command;

class EncryptUserData extends Command
{
    protected $signature = 'users:encrypt-data';
    protected $description = 'Encrypt all user SSN data';
    
    public function handle(): int
    {
        $users = User::whereNotNull('ssn_plain')->get();
        
        $bar = $this->output->createProgressBar($users->count());
        
        foreach ($users as $user) {
            $user->update([
                'ssn' => crypto()->encrypt($user->ssn_plain),
                'ssn_plain' => null,
            ]);
            
            $bar->advance();
        }
        
        $bar->finish();
        $this->newLine();
        $this->info('All data encrypted successfully!');
        
        return Command::SUCCESS;
    }
}
```

## Advanced Usage

### Custom Configuration

```php
// Change algorithm on the fly
$encrypted = crypto()
    ->setAlgorithm('AES-128-CBC')
    ->encrypt('data');

// Use different key
$encrypted = crypto()
    ->setKey('custom-key')
    ->encrypt('data');

// Chain multiple operations
$result = crypto()
    ->setKey('key-1')
    ->setAlgorithm('AES-256-CBC')
    ->encrypt('sensitive data');
```

### Batch Operations

```php
$data = [
    'ssn' => '123-45-6789',
    'credit_card' => '4111-1111-1111-1111',
    'bank_account' => '1234567890',
];

$encrypted = array_map(
    fn($value) => crypto()->encrypt($value),
    $data
);

// Later, decrypt
$decrypted = array_map(
    fn($value) => crypto()->decrypt($value),
    $encrypted
);
```

### Error Handling

```php
try {
    $decrypted = crypto()->decrypt($encrypted);
    
    if ($decrypted === false) {
        throw new \Exception('Decryption failed');
    }
    
    // Use decrypted data
} catch (\Exception $e) {
    logger()->error('Crypto error', [
        'message' => $e->getMessage(),
        'data_length' => strlen($encrypted),
    ]);
    
    return response()->json(['error' => 'Unable to decrypt data'], 500);
}
```

## IDE Setup for Autocomplete

### PHPStorm / IntelliJ IDEA

Autocomplete should work automatically. If not:

1. Clear cache: `File > Invalidate Caches > Invalidate and Restart`
2. Regenerate helpers: `php artisan ide-helper:generate`

### VS Code

1. Install **PHP Intelephense** extension
2. Reload VS Code
3. Autocomplete should work automatically

### Laravel IDE Helper (Optional)

For even better autocomplete:

```bash
composer require --dev barryvdh/laravel-ide-helper

php artisan ide-helper:generate
php artisan ide-helper:models
```

## Comparison

### Facade vs Helper

```php
// Facade - No autocomplete ❌
use Akira\LaravelCrypto\Facades\Crypto;
Crypto::encrypt('data');

// Helper - Full autocomplete ✅
crypto()->encrypt('data');

// Dependency Injection - Full autocomplete ✅
public function __construct(private LaravelCrypto $crypto) {}
$this->crypto->encrypt('data');
```

### Performance

The helper function has **zero performance overhead**:
- Uses Laravel's service container
- Returns singleton instance
- Same performance as facade or DI

## Testing with Helper

```php
use Tests\TestCase;

class HelperTest extends TestCase
{
    public function test_can_encrypt_with_helper()
    {
        $data = 'test data';
        $encrypted = crypto()->encrypt($data);
        
        $this->assertIsString($encrypted);
        $this->assertNotEquals($data, $encrypted);
    }
    
    public function test_can_decrypt_with_helper()
    {
        $data = 'test data';
        $encrypted = crypto()->encrypt($data);
        $decrypted = crypto()->decrypt($encrypted);
        
        $this->assertEquals($data, $decrypted);
    }
}
```

## Best Practices

### ✅ Do

```php
// Use the helper for clean code
$encrypted = crypto()->encrypt($data);

// Check decryption results
$decrypted = crypto()->decrypt($encrypted);
if ($decrypted === false) {
    // Handle error
}

// Type cast when needed
$encrypted = crypto()->encrypt((string) $value);
```

### ❌ Don't

```php
// Don't ignore decryption failures
$decrypted = crypto()->decrypt($encrypted);
echo $decrypted; // Could output "false" as string!

// Don't re-encrypt encrypted data
if (crypto()->decrypt($value) !== false) {
    $value = crypto()->encrypt($value); // Already encrypted!
}
```

## Migration from Facade

If you're using the facade, you can easily migrate:

```php
// Before
use Akira\LaravelCrypto\Facades\Crypto;
$encrypted = Crypto::encrypt($data);

// After - just remove the import
$encrypted = crypto()->encrypt($data);
```

**Find and replace:**
```bash
# Find
Crypto::

# Replace with
crypto()->
```

## Next Steps

- [See more examples](06-examples.md)
- [Advanced usage patterns](05-advanced-usage.md)
- [API reference](07-api-reference.md)

**Previous:** [FAQ](10-faq.md)
