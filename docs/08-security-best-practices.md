# Security Best Practices

## Key Management

### 1. Generate Strong Keys

Always use the provided artisan command to generate keys:

```bash
php artisan crypto:generate-key --length=64
```

**Why?**
- Uses cryptographically secure random bytes
- Generates keys of appropriate length
- Ensures proper entropy

**Don't:**
```php
❌ 'encryption_key' => 'mypassword123'
❌ 'encryption_key' => str_repeat('a', 64)
❌ 'encryption_key' => md5('something')
```

**Do:**
```php
✅ 'encryption_key' => env('CRYPTO_ENCRYPTION_KEY')
✅ Generated via: php artisan crypto:generate-key
```

### 2. Store Keys Securely

**Environment Variables**

Store keys in `.env` file (never commit to version control):

```env
CRYPTO_ENCRYPTION_KEY=base64:generated-secure-key-here
```

Add to `.gitignore`:
```
.env
.env.*
!.env.example
```

**External Secret Management**

For production, use secret management services:

```php
// AWS Secrets Manager
'encryption_key' => aws_secret('crypto-key'),

// HashiCorp Vault
'encryption_key' => vault_secret('app/crypto-key'),

// Azure Key Vault
'encryption_key' => azure_secret('crypto-key'),
```

### 3. Key Rotation

Implement key rotation for long-term security:

```php
use Akira\LaravelCrypto\Facades\Crypto;

class KeyRotationService
{
    public function rotateKey(string $newKey): void
    {
        $oldKey = Crypto::getKey();
        
        // Get all encrypted records
        $records = DB::table('sensitive_data')->get();
        
        foreach ($records as $record) {
            // Decrypt with old key
            $decrypted = Crypto::setKey($oldKey)->decrypt($record->data);
            
            if ($decrypted !== false) {
                // Re-encrypt with new key
                $encrypted = Crypto::setKey($newKey)->encrypt($decrypted);
                
                // Update record
                DB::table('sensitive_data')
                    ->where('id', $record->id)
                    ->update(['data' => $encrypted]);
            }
        }
        
        // Update key in configuration
        $this->updateEnvironmentKey($newKey);
    }
    
    private function updateEnvironmentKey(string $key): void
    {
        $envFile = base_path('.env');
        $content = file_get_contents($envFile);
        
        $content = preg_replace(
            '/CRYPTO_ENCRYPTION_KEY=.*/',
            "CRYPTO_ENCRYPTION_KEY={$key}",
            $content
        );
        
        file_put_contents($envFile, $content);
    }
}
```

**Key Rotation Schedule:**
- Development: Not required
- Staging: Every 6-12 months
- Production: Every 3-6 months or after security incident

### 4. Separate Keys for Different Purposes

Use different keys for different types of data:

```env
# User data encryption
USER_DATA_KEY=key1...

# Payment information
PAYMENT_KEY=key2...

# API tokens
API_TOKEN_KEY=key3...

# Backup data
BACKUP_KEY=key4...
```

```php
// Separate crypto instances
$userCrypto = new LaravelCrypto(['encryption_key' => env('USER_DATA_KEY')]);
$paymentCrypto = new LaravelCrypto(['encryption_key' => env('PAYMENT_KEY')]);
```

## Data Protection

### 5. Encrypt Only What's Necessary

**Do Encrypt:**
- ✅ Personal Identifiable Information (PII)
- ✅ Social Security Numbers
- ✅ Credit card numbers
- ✅ Bank account information
- ✅ Medical records
- ✅ API keys and tokens
- ✅ Passwords (use hashing + encryption)
- ✅ Private communications

**Don't Encrypt:**
- ❌ Public information
- ❌ Non-sensitive IDs
- ❌ Timestamps
- ❌ Public product descriptions
- ❌ Already hashed data (unless double protection needed)

### 6. Handle Decryption Failures Gracefully

```php
// Bad - Silent failure
$decrypted = Crypto::decrypt($encrypted);
echo $decrypted; // Could output "false"

// Good - Proper error handling
$decrypted = Crypto::decrypt($encrypted);

if ($decrypted === false) {
    logger()->warning('Decryption failed', [
        'user_id' => auth()->id(),
        'context' => 'user_profile',
    ]);
    
    return response()->json([
        'error' => 'Unable to access encrypted data'
    ], 500);
}

return $decrypted;
```

### 7. Validate Data Before Encryption

```php
// Validate input
$validated = $request->validate([
    'ssn' => 'required|regex:/^\d{3}-\d{2}-\d{4}$/',
]);

// Sanitize
$ssn = strip_tags($validated['ssn']);

// Encrypt
$encrypted = Crypto::encrypt($ssn);
```

### 8. Don't Log Decrypted Sensitive Data

```php
// Bad
logger()->info('User SSN: ' . $decryptedSSN);

// Good
logger()->info('User SSN accessed', [
    'user_id' => $user->id,
    'accessed_at' => now(),
    'masked' => 'XXX-XX-' . substr($decryptedSSN, -4),
]);
```

## Access Control

### 9. Implement Role-Based Access

```php
use Akira\LaravelCrypto\Facades\Crypto;

class SecureDataPolicy
{
    public function viewSensitiveData(User $user, Model $model): bool
    {
        // Only admins and data owners can decrypt
        return $user->isAdmin() || $user->id === $model->user_id;
    }
}

// In controller
public function show(Request $request, User $user)
{
    $this->authorize('viewSensitiveData', $user);
    
    $ssn = Crypto::decrypt($user->ssn);
    
    return view('user.show', compact('user', 'ssn'));
}
```

### 10. Audit Encrypted Data Access

```php
class AuditedDecryption
{
    public function decrypt(string $encrypted, string $context): string|false
    {
        $decrypted = Crypto::decrypt($encrypted);
        
        // Log access
        DB::table('data_access_logs')->insert([
            'user_id' => auth()->id(),
            'context' => $context,
            'ip_address' => request()->ip(),
            'accessed_at' => now(),
            'success' => $decrypted !== false,
        ]);
        
        return $decrypted;
    }
}
```

## Network Security

### 11. Use HTTPS

Always transmit encrypted data over HTTPS:

```php
// Force HTTPS in production
if (app()->environment('production')) {
    URL::forceScheme('https');
}
```

### 12. Don't Expose Encrypted Data in URLs

```php
// Bad
Route::get('/user/{encryptedId}', function ($encryptedId) {
    // Encrypted data in URL is visible in logs
});

// Good
Route::get('/user/{id}', function ($id) {
    $user = User::find($id);
    $decrypted = Crypto::decrypt($user->sensitive_field);
});
```

### 13. Secure API Endpoints

```php
// Middleware for encrypted API
class VerifyEncryptedRequest
{
    public function handle(Request $request, Closure $next)
    {
        if (!$request->secure()) {
            return response()->json(['error' => 'HTTPS required'], 403);
        }
        
        if (!$request->hasHeader('X-Signature')) {
            return response()->json(['error' => 'Missing signature'], 401);
        }
        
        // Verify request signature
        $signature = hash_hmac('sha256', $request->getContent(), env('API_SECRET'));
        
        if (!hash_equals($signature, $request->header('X-Signature'))) {
            return response()->json(['error' => 'Invalid signature'], 401);
        }
        
        return $next($request);
    }
}
```

## Database Security

### 14. Use Appropriate Column Types

```php
Schema::create('users', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->string('email')->unique();
    
    // TEXT for encrypted data (can be long)
    $table->text('ssn');
    $table->text('credit_card');
    $table->text('medical_records');
    
    $table->timestamps();
});
```

### 15. Create Indexes Carefully

Don't index encrypted columns directly:

```php
// Bad
$table->text('ssn')->index(); // Won't work efficiently

// Good - Use hash for indexing
$table->text('ssn');
$table->string('ssn_hash', 64)->index();
```

Implementation:

```php
public function setSsnAttribute($value)
{
    $this->attributes['ssn'] = Crypto::encrypt($value);
    $this->attributes['ssn_hash'] = hash('sha256', $value);
}

public static function findBySSN(string $ssn): ?self
{
    $hash = hash('sha256', $ssn);
    return static::where('ssn_hash', $hash)->first();
}
```

### 16. Backup Encrypted Data Securely

```php
// Backup script
class SecureBackup
{
    public function createBackup(): string
    {
        $data = DB::table('sensitive_data')->get();
        
        // Data is already encrypted in database
        $backup = json_encode($data);
        
        // Encrypt the entire backup with a different key
        $backupKey = env('BACKUP_ENCRYPTION_KEY');
        $encryptedBackup = Crypto::setKey($backupKey)->encrypt($backup);
        
        $filename = 'backup_' . date('Y-m-d') . '.enc';
        Storage::put('backups/' . $filename, $encryptedBackup);
        
        return $filename;
    }
    
    public function restoreBackup(string $filename): bool
    {
        $encrypted = Storage::get('backups/' . $filename);
        
        $backupKey = env('BACKUP_ENCRYPTION_KEY');
        $decrypted = Crypto::setKey($backupKey)->decrypt($encrypted);
        
        if ($decrypted === false) {
            return false;
        }
        
        $data = json_decode($decrypted, true);
        
        // Restore data
        foreach ($data as $record) {
            DB::table('sensitive_data')->insert($record);
        }
        
        return true;
    }
}
```

## Application Security

### 17. Implement Rate Limiting

Prevent brute force attacks on encrypted data:

```php
// In routes/api.php
Route::middleware('throttle:10,1')->group(function () {
    Route::post('/decrypt', [EncryptionController::class, 'decrypt']);
});

// Custom rate limiter
RateLimiter::for('decryption', function (Request $request) {
    return Limit::perMinute(5)->by($request->user()?->id ?: $request->ip());
});
```

### 18. Validate Data Integrity

Use authenticated encryption when possible:

```php
// Use AES-GCM for authenticated encryption
config(['crypto.algorithm' => 'AES-256-GCM']);

$encrypted = Crypto::encrypt($data);

// GCM provides authentication, preventing tampering
$decrypted = Crypto::decrypt($encrypted);
// Will fail if data was tampered with
```

### 19. Secure Error Messages

Don't leak information in error messages:

```php
// Bad
catch (Exception $e) {
    return response()->json([
        'error' => $e->getMessage() // May contain sensitive info
    ]);
}

// Good
catch (Exception $e) {
    logger()->error('Encryption error', [
        'message' => $e->getMessage(),
        'trace' => $e->getTraceAsString(),
    ]);
    
    return response()->json([
        'error' => 'An error occurred processing your request'
    ], 500);
}
```

### 20. Input Validation

Always validate before encrypting:

```php
class EncryptionValidator
{
    public static function validateBeforeEncrypt($data): bool
    {
        // Check data type
        if (!is_string($data)) {
            return false;
        }
        
        // Check length (reasonable limits)
        if (strlen($data) > 1000000) { // 1MB
            return false;
        }
        
        // Check for null bytes (can cause issues)
        if (strpos($data, "\0") !== false) {
            return false;
        }
        
        return true;
    }
}
```

## Compliance

### 21. GDPR Compliance

Implement right to erasure:

```php
class GDPRCompliance
{
    public function eraseUserData(int $userId): void
    {
        // Log erasure
        DB::table('gdpr_erasure_log')->insert([
            'user_id' => $userId,
            'erased_at' => now(),
            'erased_by' => auth()->id(),
        ]);
        
        // Delete encrypted data
        DB::table('user_profiles')
            ->where('user_id', $userId)
            ->delete();
        
        // Overwrite sensitive fields instead of deleting
        User::where('id', $userId)->update([
            'ssn' => null,
            'credit_card' => null,
        ]);
    }
}
```

### 22. HIPAA Compliance

For healthcare data:

```php
class HIPAACompliant
{
    // Use strong encryption
    private LaravelCrypto $crypto;
    
    public function __construct()
    {
        $this->crypto = new LaravelCrypto([
            'algorithm' => 'AES-256-GCM', // Authenticated encryption
            'encryption_key' => env('HIPAA_ENCRYPTION_KEY'),
            'key_size' => 32,
            'interactions' => 100000, // High iteration count
        ]);
    }
    
    public function storePatientRecord(array $data): void
    {
        $encrypted = $this->crypto->encrypt(json_encode($data));
        
        // Audit trail
        DB::table('hipaa_audit')->insert([
            'action' => 'store_patient_record',
            'user_id' => auth()->id(),
            'timestamp' => now(),
            'ip_address' => request()->ip(),
        ]);
        
        DB::table('patient_records')->insert([
            'data' => $encrypted,
            'created_at' => now(),
        ]);
    }
}
```

### 23. PCI DSS Compliance

For payment card data:

```php
class PCIDSSCompliant
{
    public function storeCardData(array $cardData): void
    {
        // Validate card number
        if (!$this->validateCard($cardData['number'])) {
            throw new \Exception('Invalid card number');
        }
        
        // Never store CVV (PCI DSS requirement)
        unset($cardData['cvv']);
        
        // Encrypt card data
        $encrypted = Crypto::encrypt(json_encode($cardData));
        
        // Store with limited access
        DB::table('payment_methods')->insert([
            'user_id' => auth()->id(),
            'card_data' => $encrypted,
            'last_four' => substr($cardData['number'], -4),
            'created_at' => now(),
        ]);
        
        // Log access
        $this->logPCIAccess('store_card');
    }
    
    private function validateCard(string $number): bool
    {
        // Luhn algorithm
        $sum = 0;
        $numDigits = strlen($number);
        $parity = $numDigits % 2;
        
        for ($i = 0; $i < $numDigits; $i++) {
            $digit = (int) $number[$i];
            
            if ($i % 2 == $parity) {
                $digit *= 2;
            }
            
            if ($digit > 9) {
                $digit -= 9;
            }
            
            $sum += $digit;
        }
        
        return $sum % 10 == 0;
    }
}
```

## Testing Security

### 24. Security Testing

```php
use Tests\TestCase;
use Akira\LaravelCrypto\Facades\Crypto;

class SecurityTest extends TestCase
{
    public function test_encryption_produces_different_outputs()
    {
        $data = 'test';
        
        $encrypted1 = Crypto::encrypt($data);
        $encrypted2 = Crypto::encrypt($data);
        
        // Due to random IV, outputs should differ
        $this->assertNotEquals($encrypted1, $encrypted2);
    }
    
    public function test_cannot_decrypt_with_wrong_key()
    {
        $data = 'secret';
        $encrypted = Crypto::setKey('key1')->encrypt($data);
        
        $decrypted = Crypto::setKey('different-key')->decrypt($encrypted);
        
        $this->assertFalse($decrypted);
    }
    
    public function test_encrypted_data_is_not_readable()
    {
        $sensitive = 'SSN: 123-45-6789';
        $encrypted = Crypto::encrypt($sensitive);
        
        // Ensure sensitive data is not in encrypted string
        $this->assertStringNotContainsString('123-45-6789', $encrypted);
        $this->assertStringNotContainsString('SSN', $encrypted);
    }
}
```

## Incident Response

### 25. Key Compromise Procedure

If a key is compromised:

1. **Immediate Actions:**
```bash
# Generate new key
php artisan crypto:generate-key --length=64

# Update .env immediately
```

2. **Rotate All Data:**
```php
php artisan crypto:rotate-key
```

3. **Audit Access:**
```php
// Check who accessed data
$logs = DB::table('data_access_logs')
    ->where('accessed_at', '>=', $compromiseDate)
    ->get();
```

4. **Notify Affected Users** (if required by law)

## Next Steps

- [Check API reference](07-api-reference.md)
- [Troubleshooting guide](09-troubleshooting.md)
- [FAQ](10-faq.md)
