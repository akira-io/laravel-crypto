<?php

use Akira\LaravelCrypto\Facades\Crypto;

beforeEach(function () {
    config([
        'crypto.encryption-key' => 'f6ef0db2a4325db6a08166b2461732c8fa2b4ba8f41cd901e15273fbb920e588',
    ]);
});

test('it can encrypt data', function () {

    $data = '{"id":3429,"date":"202409051600","type":"3","name":"kid akira","route":"1"}';

    $encrypted = Crypto::encrypt($data);

    expect($encrypted)->toBeString()
        ->and($encrypted)->not->toBe($data);
});

test('it can encrypt and decrypt data', function () {
    $data = 'Hello World';
    $encrypted = Crypto::encrypt($data);
    $decrypted = Crypto::decrypt($encrypted);

    expect($decrypted)->toBe($data);
});

test('it handles long data encryption and decryption', function () {
    $data = str_repeat('LongData', 1000); // Generate a long string

    $encrypted = Crypto::encrypt($data);
    $decrypted = Crypto::decrypt($encrypted);

    expect($decrypted)->toBe($data); // Long data should be handled properly
});
