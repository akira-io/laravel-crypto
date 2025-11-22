# Advanced Usage

## Custom Encryption Instances

### Multiple Encryption Contexts

Create different encryption instances for different purposes:

```php
use Akira\LaravelCrypto\LaravelCrypto;

class EncryptionService
{
    private LaravelCrypto $userDataCrypto;
    private LaravelCrypto $paymentCrypto;
    private LaravelCrypto $auditCrypto;
    
    public function __construct()
    {
        // User data encryption (standard security)
        $this->userDataCrypto = new LaravelCrypto([
            'encryption_key' => env('USER_DATA_KEY'),
            'algorithm' => 'AES-256-CBC',
            'key_size' => 32,
            'interactions' => 10000,
        ]);
        
        // Payment encryption (high security)
        $this->paymentCrypto = new LaravelCrypto([
            'encryption_key' => env('PAYMENT_KEY'),
            'algorithm' => 'AES-256-GCM',
            'key_size' => 32,
            'interactions' => 100000,
        ]);
        
        // Audit logs (fast encryption)
        $this->auditCrypto = new LaravelCrypto([
            'encryption_key' => env('AUDIT_KEY'),
            'algorithm' => 'AES-128-CBC',
            'key_size' => 16,
            'interactions' => 5000,
        ]);
    }
    
    public function encryptUserData(string $data): string
    {
        return $this->userDataCrypto->encrypt($data);
    }
    
    public function encryptPayment(array $paymentData): string
    {
        return $this->paymentCrypto->encrypt(json_encode($paymentData));
    }
    
    public function encryptAuditLog(string $log): string
    {
        return $this->auditCrypto->encrypt($log);
    }
}
```

### Runtime Configuration Changes

```php
use Akira\LaravelCrypto\Facades\Crypto;

// Switch to high-security mode for sensitive operation
$originalAlgorithm = Crypto::getAlgorithm();
$originalKey = Crypto::getKey();

Crypto::setAlgorithm('AES-256-GCM')
      ->setKey(env('HIGH_SECURITY_KEY'));

$encrypted = Crypto::encrypt($highlySecretData);

// Restore original configuration
Crypto::setAlgorithm($originalAlgorithm)
      ->setKey($originalKey);
```

## Database Integration

### Custom Encrypted Cast

```php
namespace App\Casts;

use Akira\LaravelCrypto\Facades\Crypto;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;

class Encrypted implements CastsAttributes
{
    public function get($model, string $key, $value, array $attributes)
    {
        if ($value === null) {
            return null;
        }
        
        $decrypted = Crypto::decrypt($value);
        return $decrypted !== false ? $decrypted : null;
    }

    public function set($model, string $key, $value, array $attributes)
    {
        if ($value === null) {
            return null;
        }
        
        return Crypto::encrypt((string) $value);
    }
}
```

Usage:

```php
use App\Casts\Encrypted;
use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    protected $casts = [
        'ssn' => Encrypted::class,
        'credit_card' => Encrypted::class,
        'bank_account' => Encrypted::class,
    ];
}

// Transparent encryption/decryption
$user = new User();
$user->ssn = '123-45-6789';  // Automatically encrypted
$user->save();

echo $user->ssn;  // Automatically decrypted: "123-45-6789"
```

### JSON Encrypted Cast

```php
namespace App\Casts;

use Akira\LaravelCrypto\Facades\Crypto;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;

class EncryptedJson implements CastsAttributes
{
    public function get($model, string $key, $value, array $attributes)
    {
        if ($value === null) {
            return null;
        }
        
        $decrypted = Crypto::decrypt($value);
        
        if ($decrypted === false) {
            return null;
        }
        
        return json_decode($decrypted, true);
    }

    public function set($model, string $key, $value, array $attributes)
    {
        if ($value === null) {
            return null;
        }
        
        return Crypto::encrypt(json_encode($value));
    }
}
```

Usage:

```php
use App\Casts\EncryptedJson;

class User extends Model
{
    protected $casts = [
        'medical_records' => EncryptedJson::class,
        'preferences' => EncryptedJson::class,
    ];
}

$user = new User();
$user->medical_records = [
    'blood_type' => 'O+',
    'allergies' => ['penicillin', 'peanuts'],
];
$user->save();

// Retrieve as array
$records = $user->medical_records;
echo $records['blood_type']; // "O+"
```

### Searchable Encrypted Fields

For fields that need to be searchable, use hash indexing:

```php
use Akira\LaravelCrypto\Facades\Crypto;
use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    public function setSsnAttribute($value)
    {
        $this->attributes['ssn'] = Crypto::encrypt($value);
        $this->attributes['ssn_hash'] = hash('sha256', $value);
    }
    
    public function getSsnAttribute($value)
    {
        return Crypto::decrypt($value);
    }
    
    public static function findBySSN($ssn)
    {
        $hash = hash('sha256', $ssn);
        return static::where('ssn_hash', $hash)->first();
    }
}
```

Migration:

```php
Schema::table('users', function (Blueprint $table) {
    $table->text('ssn');
    $table->string('ssn_hash', 64)->index();
});
```

Usage:

```php
$user = User::findBySSN('123-45-6789');
```

## File Encryption

### Encrypt Files

```php
use Akira\LaravelCrypto\Facades\Crypto;
use Illuminate\Support\Facades\Storage;

class FileEncryptor
{
    public function encryptFile(string $path): bool
    {
        if (!Storage::exists($path)) {
            return false;
        }
        
        $content = Storage::get($path);
        $encrypted = Crypto::encrypt($content);
        
        Storage::put($path . '.encrypted', $encrypted);
        
        return true;
    }
    
    public function decryptFile(string $encryptedPath): string|false
    {
        if (!Storage::exists($encryptedPath)) {
            return false;
        }
        
        $encrypted = Storage::get($encryptedPath);
        return Crypto::decrypt($encrypted);
    }
}
```

### Stream Large Files

For large files, encrypt in chunks:

```php
use Akira\LaravelCrypto\Facades\Crypto;
use Illuminate\Support\Facades\Storage;

class ChunkedFileEncryptor
{
    private const CHUNK_SIZE = 1024 * 1024; // 1MB chunks
    
    public function encryptLargeFile(string $inputPath, string $outputPath): bool
    {
        $input = fopen(Storage::path($inputPath), 'rb');
        $output = fopen(Storage::path($outputPath), 'wb');
        
        if (!$input || !$output) {
            return false;
        }
        
        while (!feof($input)) {
            $chunk = fread($input, self::CHUNK_SIZE);
            $encrypted = Crypto::encrypt($chunk);
            fwrite($output, $encrypted . "\n");
        }
        
        fclose($input);
        fclose($output);
        
        return true;
    }
    
    public function decryptLargeFile(string $encryptedPath, string $outputPath): bool
    {
        $input = fopen(Storage::path($encryptedPath), 'rb');
        $output = fopen(Storage::path($outputPath), 'wb');
        
        if (!$input || !$output) {
            return false;
        }
        
        while (($line = fgets($input)) !== false) {
            $decrypted = Crypto::decrypt(trim($line));
            if ($decrypted !== false) {
                fwrite($output, $decrypted);
            }
        }
        
        fclose($input);
        fclose($output);
        
        return true;
    }
}
```

## Queue Integration

### Encrypt Job Payload

```php
use Akira\LaravelCrypto\Facades\Crypto;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessSensitiveData implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    
    public string $encryptedData;
    
    public function __construct(array $sensitiveData)
    {
        $this->encryptedData = Crypto::encrypt(json_encode($sensitiveData));
    }
    
    public function handle()
    {
        $decrypted = Crypto::decrypt($this->encryptedData);
        
        if ($decrypted === false) {
            $this->fail('Failed to decrypt data');
            return;
        }
        
        $data = json_decode($decrypted, true);
        
        // Process sensitive data
        $this->processSensitiveData($data);
    }
    
    private function processSensitiveData(array $data): void
    {
        // Your processing logic
    }
}
```

### Encrypted Job Trait

```php
namespace App\Jobs\Traits;

use Akira\LaravelCrypto\Facades\Crypto;

trait EncryptsData
{
    protected function encryptPayload(array $data): string
    {
        return Crypto::encrypt(json_encode($data));
    }
    
    protected function decryptPayload(string $encrypted): array|false
    {
        $decrypted = Crypto::decrypt($encrypted);
        
        if ($decrypted === false) {
            return false;
        }
        
        return json_decode($decrypted, true);
    }
}
```

## API Integration

### Encrypt API Responses

```php
use Akira\LaravelCrypto\Facades\Crypto;
use Illuminate\Http\JsonResponse;

class EncryptedResponseController
{
    public function getSecureData(): JsonResponse
    {
        $data = [
            'user_id' => 123,
            'ssn' => '123-45-6789',
            'credit_card' => '4111-1111-1111-1111',
        ];
        
        return response()->json([
            'encrypted' => true,
            'data' => Crypto::encrypt(json_encode($data))
        ]);
    }
}
```

### Middleware for Encrypted Requests/Responses

```php
namespace App\Http\Middleware;

use Akira\LaravelCrypto\Facades\Crypto;
use Closure;
use Illuminate\Http\Request;

class EncryptedApi
{
    public function handle(Request $request, Closure $next)
    {
        // Decrypt incoming request
        if ($request->has('encrypted_data')) {
            $decrypted = Crypto::decrypt($request->input('encrypted_data'));
            
            if ($decrypted === false) {
                return response()->json(['error' => 'Invalid encrypted data'], 400);
            }
            
            $data = json_decode($decrypted, true);
            $request->merge($data);
        }
        
        $response = $next($request);
        
        // Encrypt outgoing response
        if ($request->wantsJson() && $request->header('X-Encrypt-Response')) {
            $original = $response->getData(true);
            $encrypted = Crypto::encrypt(json_encode($original));
            
            return response()->json([
                'encrypted' => true,
                'data' => $encrypted
            ]);
        }
        
        return $response;
    }
}
```

## Cache Integration

### Encrypt Cached Data

```php
use Akira\LaravelCrypto\Facades\Crypto;
use Illuminate\Support\Facades\Cache;

class SecureCache
{
    public static function put(string $key, $value, $ttl = null): bool
    {
        $encrypted = Crypto::encrypt(serialize($value));
        return Cache::put($key, $encrypted, $ttl);
    }
    
    public static function get(string $key, $default = null)
    {
        $encrypted = Cache::get($key);
        
        if ($encrypted === null) {
            return $default;
        }
        
        $decrypted = Crypto::decrypt($encrypted);
        
        if ($decrypted === false) {
            return $default;
        }
        
        return unserialize($decrypted);
    }
    
    public static function remember(string $key, $ttl, callable $callback)
    {
        $value = self::get($key);
        
        if ($value !== null) {
            return $value;
        }
        
        $value = $callback();
        self::put($key, $value, $ttl);
        
        return $value;
    }
}
```

Usage:

```php
// Store encrypted data in cache
SecureCache::put('user:123:ssn', '123-45-6789', 3600);

// Retrieve and decrypt
$ssn = SecureCache::get('user:123:ssn');

// Remember with encryption
$data = SecureCache::remember('sensitive:data', 3600, function () {
    return ['secret' => 'value'];
});
```

## Session Integration

### Encrypt Session Data

```php
namespace App\Http\Middleware;

use Akira\LaravelCrypto\Facades\Crypto;
use Closure;
use Illuminate\Http\Request;

class EncryptSensitiveSessionData
{
    protected array $encryptedKeys = [
        'ssn',
        'credit_card',
        'bank_account',
    ];
    
    public function handle(Request $request, Closure $next)
    {
        // Decrypt on read
        foreach ($this->encryptedKeys as $key) {
            if ($request->session()->has($key)) {
                $encrypted = $request->session()->get($key);
                $decrypted = Crypto::decrypt($encrypted);
                
                if ($decrypted !== false) {
                    $request->session()->put($key, $decrypted);
                }
            }
        }
        
        $response = $next($request);
        
        // Encrypt on write
        foreach ($this->encryptedKeys as $key) {
            if ($request->session()->has($key)) {
                $value = $request->session()->get($key);
                $encrypted = Crypto::encrypt($value);
                $request->session()->put($key, $encrypted);
            }
        }
        
        return $response;
    }
}
```

## Logging Integration

### Encrypt Sensitive Log Data

```php
use Akira\LaravelCrypto\Facades\Crypto;
use Monolog\Processor\ProcessorInterface;

class EncryptSensitiveDataProcessor implements ProcessorInterface
{
    protected array $sensitiveKeys = [
        'password',
        'ssn',
        'credit_card',
        'api_key',
    ];
    
    public function __invoke(array $record): array
    {
        if (isset($record['context'])) {
            $record['context'] = $this->encryptSensitiveData($record['context']);
        }
        
        return $record;
    }
    
    protected function encryptSensitiveData(array $data): array
    {
        foreach ($data as $key => $value) {
            if (in_array($key, $this->sensitiveKeys)) {
                $data[$key] = Crypto::encrypt((string) $value);
            } elseif (is_array($value)) {
                $data[$key] = $this->encryptSensitiveData($value);
            }
        }
        
        return $data;
    }
}
```

Register in `config/logging.php`:

```php
'channels' => [
    'stack' => [
        'driver' => 'stack',
        'channels' => ['single'],
        'processors' => [
            App\Logging\EncryptSensitiveDataProcessor::class,
        ],
    ],
],
```

## Testing Helpers

### Test Helper Trait

```php
namespace Tests\Traits;

use Akira\LaravelCrypto\Facades\Crypto;

trait InteractsWithEncryption
{
    protected function encryptForTest($value): string
    {
        return Crypto::encrypt(is_string($value) ? $value : json_encode($value));
    }
    
    protected function decryptForTest(string $encrypted)
    {
        $decrypted = Crypto::decrypt($encrypted);
        
        if ($decrypted === false) {
            return null;
        }
        
        $decoded = json_decode($decrypted, true);
        return json_last_error() === JSON_ERROR_NONE ? $decoded : $decrypted;
    }
    
    protected function assertEncrypted(string $value): void
    {
        $this->assertNotEquals($value, $this->decryptForTest($value));
    }
    
    protected function assertDecryptsTo(string $encrypted, $expected): void
    {
        $decrypted = $this->decryptForTest($encrypted);
        $this->assertEquals($expected, $decrypted);
    }
}
```

Usage in tests:

```php
use Tests\Traits\InteractsWithEncryption;

class UserTest extends TestCase
{
    use InteractsWithEncryption;
    
    public function test_user_ssn_is_encrypted()
    {
        $user = User::factory()->create([
            'ssn' => '123-45-6789',
        ]);
        
        $this->assertEncrypted($user->getRawOriginal('ssn'));
        $this->assertEquals('123-45-6789', $user->ssn);
    }
}
```

## Performance Optimization

### Batch Encryption

```php
use Akira\LaravelCrypto\Facades\Crypto;

class BatchEncryptor
{
    public function encryptBatch(array $items): array
    {
        return array_map(function ($item) {
            return Crypto::encrypt(is_string($item) ? $item : json_encode($item));
        }, $items);
    }
    
    public function decryptBatch(array $encryptedItems): array
    {
        return array_filter(array_map(function ($encrypted) {
            return Crypto::decrypt($encrypted);
        }, $encryptedItems), fn ($item) => $item !== false);
    }
}
```

### Lazy Decryption

```php
class LazyEncryptedValue
{
    private string $encrypted;
    private $decrypted = null;
    private bool $decryptAttempted = false;
    
    public function __construct(string $encrypted)
    {
        $this->encrypted = $encrypted;
    }
    
    public function getValue()
    {
        if (!$this->decryptAttempted) {
            $this->decrypted = Crypto::decrypt($this->encrypted);
            $this->decryptAttempted = true;
        }
        
        return $this->decrypted;
    }
}
```

## Next Steps

- [See practical examples](06-examples.md)
- [Learn security best practices](08-security-best-practices.md)
- [Check API reference](07-api-reference.md)
