# Examples

This page contains practical, real-world examples of using Laravel Crypto.

## User Management Examples

### Example 1: Encrypting User SSN

```php
use Akira\LaravelCrypto\Facades\Crypto;
use App\Models\User;

class UserController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'email' => 'required|email|unique:users',
            'ssn' => 'required|string',
        ]);
        
        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'ssn' => Crypto::encrypt($validated['ssn']),
        ]);
        
        return response()->json([
            'message' => 'User created successfully',
            'user' => $user->only(['id', 'name', 'email'])
        ]);
    }
    
    public function show(User $user)
    {
        $decryptedSSN = Crypto::decrypt($user->ssn);
        
        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'ssn' => $decryptedSSN !== false ? 
                    'XXX-XX-' . substr($decryptedSSN, -4) : null,
            ]
        ]);
    }
}
```

### Example 2: User Profile with Multiple Encrypted Fields

```php
use Akira\LaravelCrypto\Facades\Crypto;
use Illuminate\Database\Eloquent\Model;

class UserProfile extends Model
{
    protected $fillable = [
        'user_id',
        'phone',
        'address',
        'bank_account',
        'medical_info',
    ];
    
    // Encrypt when setting
    public function setPhoneAttribute($value)
    {
        $this->attributes['phone'] = Crypto::encrypt($value);
    }
    
    public function setAddressAttribute($value)
    {
        $this->attributes['address'] = Crypto::encrypt(json_encode($value));
    }
    
    public function setBankAccountAttribute($value)
    {
        $this->attributes['bank_account'] = Crypto::encrypt($value);
    }
    
    public function setMedicalInfoAttribute($value)
    {
        $this->attributes['medical_info'] = Crypto::encrypt(json_encode($value));
    }
    
    // Decrypt when getting
    public function getPhoneAttribute($value)
    {
        $decrypted = Crypto::decrypt($value);
        return $decrypted !== false ? $decrypted : null;
    }
    
    public function getAddressAttribute($value)
    {
        $decrypted = Crypto::decrypt($value);
        if ($decrypted === false) return null;
        return json_decode($decrypted, true);
    }
    
    public function getBankAccountAttribute($value)
    {
        $decrypted = Crypto::decrypt($value);
        return $decrypted !== false ? $decrypted : null;
    }
    
    public function getMedicalInfoAttribute($value)
    {
        $decrypted = Crypto::decrypt($value);
        if ($decrypted === false) return null;
        return json_decode($decrypted, true);
    }
}

// Usage
$profile = new UserProfile();
$profile->phone = '+1-555-123-4567';
$profile->address = [
    'street' => '123 Main St',
    'city' => 'New York',
    'zip' => '10001',
];
$profile->bank_account = '1234567890';
$profile->medical_info = [
    'blood_type' => 'O+',
    'allergies' => ['peanuts'],
];
$profile->save();

// Retrieve
echo $profile->phone; // "+1-555-123-4567"
print_r($profile->address); // Array with decrypted address
```

## Payment Processing Examples

### Example 3: Encrypt Credit Card Information

```php
use Akira\LaravelCrypto\Facades\Crypto;
use Illuminate\Support\Facades\DB;

class PaymentService
{
    public function storePaymentMethod(int $userId, array $cardData): array
    {
        $encryptedCard = Crypto::encrypt(json_encode([
            'number' => $cardData['number'],
            'exp_month' => $cardData['exp_month'],
            'exp_year' => $cardData['exp_year'],
            'cvv' => $cardData['cvv'],
        ]));
        
        $paymentMethodId = DB::table('payment_methods')->insertGetId([
            'user_id' => $userId,
            'card_data' => $encryptedCard,
            'last_four' => substr($cardData['number'], -4),
            'card_type' => $this->detectCardType($cardData['number']),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        return [
            'id' => $paymentMethodId,
            'last_four' => substr($cardData['number'], -4),
        ];
    }
    
    public function processPayment(int $paymentMethodId, float $amount): bool
    {
        $paymentMethod = DB::table('payment_methods')
            ->where('id', $paymentMethodId)
            ->first();
        
        if (!$paymentMethod) {
            throw new \Exception('Payment method not found');
        }
        
        $decrypted = Crypto::decrypt($paymentMethod->card_data);
        
        if ($decrypted === false) {
            throw new \Exception('Failed to decrypt payment data');
        }
        
        $cardData = json_decode($decrypted, true);
        
        // Process payment with external gateway
        $result = $this->chargeCard($cardData, $amount);
        
        return $result;
    }
    
    private function detectCardType(string $number): string
    {
        $first = substr($number, 0, 1);
        return match($first) {
            '4' => 'Visa',
            '5' => 'Mastercard',
            '3' => 'Amex',
            default => 'Unknown',
        };
    }
    
    private function chargeCard(array $cardData, float $amount): bool
    {
        // Integration with payment gateway
        return true;
    }
}
```

### Example 4: Tokenized Payment System

```php
use Akira\LaravelCrypto\Facades\Crypto;
use Illuminate\Support\Str;

class TokenizedPaymentService
{
    public function tokenizeCard(array $cardData): string
    {
        $token = Str::random(32);
        
        $encrypted = Crypto::encrypt(json_encode($cardData));
        
        cache()->put("payment_token:{$token}", $encrypted, now()->addHours(1));
        
        return $token;
    }
    
    public function chargeToken(string $token, float $amount): array
    {
        $encrypted = cache()->get("payment_token:{$token}");
        
        if (!$encrypted) {
            throw new \Exception('Token expired or invalid');
        }
        
        $decrypted = Crypto::decrypt($encrypted);
        
        if ($decrypted === false) {
            throw new \Exception('Failed to decrypt payment data');
        }
        
        $cardData = json_decode($decrypted, true);
        
        // Process payment
        $transactionId = Str::uuid();
        
        // Clear token after use
        cache()->forget("payment_token:{$token}");
        
        return [
            'success' => true,
            'transaction_id' => $transactionId,
            'amount' => $amount,
        ];
    }
}

// Usage
$service = new TokenizedPaymentService();

// Step 1: Tokenize card
$token = $service->tokenizeCard([
    'number' => '4111111111111111',
    'exp_month' => '12',
    'exp_year' => '2025',
    'cvv' => '123',
]);

// Step 2: Charge token (can be done in different request)
$result = $service->chargeToken($token, 99.99);
```

## API Integration Examples

### Example 5: Encrypted API Tokens Storage

```php
use Akira\LaravelCrypto\Facades\Crypto;
use Illuminate\Database\Eloquent\Model;

class ApiIntegration extends Model
{
    protected $fillable = ['name', 'service', 'api_key', 'api_secret'];
    
    public function setApiKeyAttribute($value)
    {
        $this->attributes['api_key'] = Crypto::encrypt($value);
    }
    
    public function setApiSecretAttribute($value)
    {
        $this->attributes['api_secret'] = Crypto::encrypt($value);
    }
    
    public function getApiKeyAttribute($value)
    {
        return Crypto::decrypt($value) ?: null;
    }
    
    public function getApiSecretAttribute($value)
    {
        return Crypto::decrypt($value) ?: null;
    }
}

// Usage
$integration = ApiIntegration::create([
    'name' => 'Stripe Integration',
    'service' => 'stripe',
    'api_key' => 'sk_live_xxxxxx',
    'api_secret' => 'secret_xxxxx',
]);

// Later retrieve
$stripe = ApiIntegration::where('service', 'stripe')->first();
$client = new StripeClient($stripe->api_key);
```

### Example 6: Encrypted API Communication

```php
use Akira\LaravelCrypto\Facades\Crypto;
use Illuminate\Support\Facades\Http;

class SecureApiClient
{
    private string $baseUrl;
    private string $sharedSecret;
    
    public function __construct(string $baseUrl, string $sharedSecret)
    {
        $this->baseUrl = $baseUrl;
        $this->sharedSecret = $sharedSecret;
    }
    
    public function sendEncryptedRequest(string $endpoint, array $data): array
    {
        // Configure crypto with shared secret
        $crypto = Crypto::setKey($this->sharedSecret);
        
        // Encrypt payload
        $encrypted = $crypto->encrypt(json_encode($data));
        
        // Send request
        $response = Http::post($this->baseUrl . $endpoint, [
            'encrypted_payload' => $encrypted,
            'timestamp' => time(),
        ]);
        
        if (!$response->successful()) {
            throw new \Exception('API request failed');
        }
        
        // Decrypt response
        $encryptedResponse = $response->json('encrypted_response');
        $decrypted = $crypto->decrypt($encryptedResponse);
        
        if ($decrypted === false) {
            throw new \Exception('Failed to decrypt response');
        }
        
        return json_decode($decrypted, true);
    }
    
    public function receiveEncryptedRequest(string $encryptedPayload): array
    {
        $crypto = Crypto::setKey($this->sharedSecret);
        
        $decrypted = $crypto->decrypt($encryptedPayload);
        
        if ($decrypted === false) {
            throw new \Exception('Failed to decrypt request');
        }
        
        return json_decode($decrypted, true);
    }
}

// Usage
$client = new SecureApiClient('https://api.example.com', env('SHARED_SECRET'));

$result = $client->sendEncryptedRequest('/user/create', [
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'ssn' => '123-45-6789',
]);
```

## File Storage Examples

### Example 7: Encrypt Uploaded Files

```php
use Akira\LaravelCrypto\Facades\Crypto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SecureFileController extends Controller
{
    public function upload(Request $request)
    {
        $request->validate([
            'file' => 'required|file|max:10240', // 10MB max
        ]);
        
        $file = $request->file('file');
        $content = file_get_contents($file->getRealPath());
        
        // Encrypt file content
        $encrypted = Crypto::encrypt($content);
        
        // Store encrypted file
        $filename = time() . '_' . $file->getClientOriginalName() . '.encrypted';
        Storage::put('secure/' . $filename, $encrypted);
        
        // Store metadata
        DB::table('secure_files')->insert([
            'filename' => $filename,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'user_id' => auth()->id(),
            'created_at' => now(),
        ]);
        
        return response()->json([
            'message' => 'File uploaded and encrypted successfully',
            'filename' => $filename,
        ]);
    }
    
    public function download(string $filename)
    {
        $fileRecord = DB::table('secure_files')
            ->where('filename', $filename)
            ->where('user_id', auth()->id())
            ->first();
        
        if (!$fileRecord) {
            abort(404);
        }
        
        $encrypted = Storage::get('secure/' . $filename);
        $decrypted = Crypto::decrypt($encrypted);
        
        if ($decrypted === false) {
            abort(500, 'Failed to decrypt file');
        }
        
        return response($decrypted)
            ->header('Content-Type', $fileRecord->mime_type)
            ->header('Content-Disposition', 'attachment; filename="' . $fileRecord->original_name . '"');
    }
}
```

### Example 8: Encrypted Document Management

```php
use Akira\LaravelCrypto\Facades\Crypto;
use Illuminate\Support\Facades\Storage;

class DocumentManager
{
    public function storeDocument(string $title, string $content, array $metadata = []): int
    {
        $encryptedContent = Crypto::encrypt($content);
        $encryptedMetadata = Crypto::encrypt(json_encode($metadata));
        
        return DB::table('documents')->insertGetId([
            'title' => $title,
            'content' => $encryptedContent,
            'metadata' => $encryptedMetadata,
            'user_id' => auth()->id(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
    
    public function getDocument(int $documentId): ?array
    {
        $document = DB::table('documents')
            ->where('id', $documentId)
            ->where('user_id', auth()->id())
            ->first();
        
        if (!$document) {
            return null;
        }
        
        $content = Crypto::decrypt($document->content);
        $metadata = Crypto::decrypt($document->metadata);
        
        if ($content === false || $metadata === false) {
            return null;
        }
        
        return [
            'id' => $document->id,
            'title' => $document->title,
            'content' => $content,
            'metadata' => json_decode($metadata, true),
            'created_at' => $document->created_at,
        ];
    }
    
    public function searchDocuments(string $query): array
    {
        // For searchable content, you'd need to implement a search index
        // This is a simplified version
        $documents = DB::table('documents')
            ->where('user_id', auth()->id())
            ->where('title', 'like', "%{$query}%")
            ->get();
        
        $results = [];
        
        foreach ($documents as $doc) {
            $content = Crypto::decrypt($doc->content);
            
            if ($content !== false && stripos($content, $query) !== false) {
                $results[] = [
                    'id' => $doc->id,
                    'title' => $doc->title,
                    'excerpt' => $this->getExcerpt($content, $query),
                ];
            }
        }
        
        return $results;
    }
    
    private function getExcerpt(string $content, string $query, int $length = 200): string
    {
        $pos = stripos($content, $query);
        $start = max(0, $pos - $length / 2);
        return '...' . substr($content, $start, $length) . '...';
    }
}
```

## Authentication Examples

### Example 9: Two-Factor Authentication Backup Codes

```php
use Akira\LaravelCrypto\Facades\Crypto;
use Illuminate\Support\Str;

class TwoFactorAuth
{
    public function generateBackupCodes(int $userId, int $count = 10): array
    {
        $codes = [];
        
        for ($i = 0; $i < $count; $i++) {
            $code = Str::upper(Str::random(8));
            $codes[] = $code;
            
            DB::table('backup_codes')->insert([
                'user_id' => $userId,
                'code' => Crypto::encrypt($code),
                'used' => false,
                'created_at' => now(),
            ]);
        }
        
        return $codes;
    }
    
    public function verifyBackupCode(int $userId, string $code): bool
    {
        $backupCodes = DB::table('backup_codes')
            ->where('user_id', $userId)
            ->where('used', false)
            ->get();
        
        foreach ($backupCodes as $record) {
            $decrypted = Crypto::decrypt($record->code);
            
            if ($decrypted === $code) {
                DB::table('backup_codes')
                    ->where('id', $record->id)
                    ->update(['used' => true, 'used_at' => now()]);
                
                return true;
            }
        }
        
        return false;
    }
}

// Usage
$tfa = new TwoFactorAuth();

// Generate codes (show once to user)
$codes = $tfa->generateBackupCodes(auth()->id());

// Verify code during login
if ($tfa->verifyBackupCode(auth()->id(), $request->input('backup_code'))) {
    auth()->login($user);
}
```

### Example 10: Encrypted Session Tokens

```php
use Akira\LaravelCrypto\Facades\Crypto;
use Illuminate\Support\Str;

class SecureSessionManager
{
    public function createSession(int $userId, array $data = []): string
    {
        $sessionId = Str::uuid()->toString();
        
        $sessionData = [
            'user_id' => $userId,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'data' => $data,
            'created_at' => time(),
        ];
        
        $encrypted = Crypto::encrypt(json_encode($sessionData));
        
        cache()->put("session:{$sessionId}", $encrypted, now()->addHours(24));
        
        return $sessionId;
    }
    
    public function getSession(string $sessionId): ?array
    {
        $encrypted = cache()->get("session:{$sessionId}");
        
        if (!$encrypted) {
            return null;
        }
        
        $decrypted = Crypto::decrypt($encrypted);
        
        if ($decrypted === false) {
            return null;
        }
        
        $sessionData = json_decode($decrypted, true);
        
        // Verify IP and user agent
        if ($sessionData['ip_address'] !== request()->ip()) {
            $this->destroySession($sessionId);
            return null;
        }
        
        return $sessionData;
    }
    
    public function destroySession(string $sessionId): void
    {
        cache()->forget("session:{$sessionId}");
    }
}
```

## Audit Log Examples

### Example 11: Encrypted Audit Logs

```php
use Akira\LaravelCrypto\Facades\Crypto;

class AuditLogger
{
    public function log(string $action, array $data, ?int $userId = null): void
    {
        $encryptedData = Crypto::encrypt(json_encode([
            'action' => $action,
            'data' => $data,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'timestamp' => now()->toDateTimeString(),
        ]));
        
        DB::table('audit_logs')->insert([
            'user_id' => $userId ?? auth()->id(),
            'action' => $action,
            'encrypted_data' => $encryptedData,
            'created_at' => now(),
        ]);
    }
    
    public function getAuditTrail(int $userId, int $limit = 100): array
    {
        $logs = DB::table('audit_logs')
            ->where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
        
        return $logs->map(function ($log) {
            $decrypted = Crypto::decrypt($log->encrypted_data);
            
            if ($decrypted === false) {
                return null;
            }
            
            $data = json_decode($decrypted, true);
            
            return [
                'id' => $log->id,
                'action' => $log->action,
                'details' => $data,
                'created_at' => $log->created_at,
            ];
        })->filter()->values()->all();
    }
}

// Usage
$logger = new AuditLogger();

$logger->log('user.login', [
    'method' => '2fa',
    'success' => true,
]);

$logger->log('profile.update', [
    'fields' => ['email', 'phone'],
    'old_email' => 'old@example.com',
    'new_email' => 'new@example.com',
]);
```

## E-commerce Examples

### Example 12: Encrypted Order Information

```php
use Akira\LaravelCrypto\Facades\Crypto;

class Order extends Model
{
    protected $fillable = [
        'user_id',
        'order_number',
        'shipping_address',
        'billing_address',
        'payment_method',
        'total',
    ];
    
    public function setShippingAddressAttribute($value)
    {
        $this->attributes['shipping_address'] = Crypto::encrypt(json_encode($value));
    }
    
    public function setBillingAddressAttribute($value)
    {
        $this->attributes['billing_address'] = Crypto::encrypt(json_encode($value));
    }
    
    public function setPaymentMethodAttribute($value)
    {
        $this->attributes['payment_method'] = Crypto::encrypt(json_encode($value));
    }
    
    public function getShippingAddressAttribute($value)
    {
        $decrypted = Crypto::decrypt($value);
        return $decrypted !== false ? json_decode($decrypted, true) : null;
    }
    
    public function getBillingAddressAttribute($value)
    {
        $decrypted = Crypto::decrypt($value);
        return $decrypted !== false ? json_decode($decrypted, true) : null;
    }
    
    public function getPaymentMethodAttribute($value)
    {
        $decrypted = Crypto::decrypt($value);
        return $decrypted !== false ? json_decode($decrypted, true) : null;
    }
}

// Usage
$order = Order::create([
    'user_id' => auth()->id(),
    'order_number' => 'ORD-' . time(),
    'shipping_address' => [
        'name' => 'John Doe',
        'street' => '123 Main St',
        'city' => 'New York',
        'zip' => '10001',
    ],
    'billing_address' => [
        'name' => 'John Doe',
        'street' => '123 Main St',
        'city' => 'New York',
        'zip' => '10001',
    ],
    'payment_method' => [
        'type' => 'credit_card',
        'last_four' => '4242',
    ],
    'total' => 99.99,
]);
```

## Healthcare Examples

### Example 13: HIPAA-Compliant Patient Records

```php
use Akira\LaravelCrypto\Facades\Crypto;

class PatientRecord extends Model
{
    protected $fillable = [
        'patient_id',
        'medical_history',
        'prescriptions',
        'diagnosis',
        'treatment_plan',
    ];
    
    // Automatically encrypt all sensitive fields
    public function setAttribute($key, $value)
    {
        if (in_array($key, ['medical_history', 'prescriptions', 'diagnosis', 'treatment_plan'])) {
            $value = Crypto::encrypt(is_array($value) ? json_encode($value) : $value);
        }
        
        return parent::setAttribute($key, $value);
    }
    
    public function getAttribute($key)
    {
        $value = parent::getAttribute($key);
        
        if (in_array($key, ['medical_history', 'prescriptions', 'diagnosis', 'treatment_plan']) && $value) {
            $decrypted = Crypto::decrypt($value);
            if ($decrypted !== false) {
                $decoded = json_decode($decrypted, true);
                return json_last_error() === JSON_ERROR_NONE ? $decoded : $decrypted;
            }
        }
        
        return $value;
    }
}

// Usage
$record = PatientRecord::create([
    'patient_id' => 123,
    'medical_history' => [
        'conditions' => ['Hypertension', 'Diabetes Type 2'],
        'surgeries' => ['Appendectomy 2010'],
    ],
    'prescriptions' => [
        ['name' => 'Metformin', 'dosage' => '500mg', 'frequency' => 'twice daily'],
    ],
    'diagnosis' => 'Type 2 Diabetes Mellitus',
    'treatment_plan' => 'Diet modification, exercise, medication',
]);
```

## Next Steps

- [Learn security best practices](08-security-best-practices.md)
- [Check API reference](07-api-reference.md)
- [Troubleshooting guide](09-troubleshooting.md)

**Previous:** [Advanced Usage](05-advanced-usage.md) | **Next:** [API Reference](07-api-reference.md)
