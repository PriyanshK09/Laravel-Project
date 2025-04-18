<?php

return [
    /*
    |--------------------------------------------------------------------------
    | OpenSSL Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains the configuration settings for OpenSSL operations
    | used in the VoIP encryption system. These settings control aspects like
    | key sizes, encryption algorithms, and other security parameters.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | RSA Configuration
    |--------------------------------------------------------------------------
    */
    'rsa' => [
        'key_bits' => env('OPENSSL_RSA_KEY_BITS', 2048),
        'key_type' => OPENSSL_KEYTYPE_RSA,
        'padding' => OPENSSL_PKCS1_PADDING,
        // Default expiry time for RSA keys (in minutes)
        'key_expiry' => env('OPENSSL_RSA_KEY_EXPIRY', 240), // 4 hours
    ],

    /*
    |--------------------------------------------------------------------------
    | AES Configuration
    |--------------------------------------------------------------------------
    */
    'aes' => [
        'cipher' => env('OPENSSL_AES_CIPHER', 'aes-256-cbc'),
        'key_length' => env('OPENSSL_AES_KEY_LENGTH', 32), // 256 bits
        'iv_length' => 16, // AES block size (16 bytes/128 bits for CBC)
        // Default expiry time for AES keys (in minutes)
        'key_expiry' => env('OPENSSL_AES_KEY_EXPIRY', 240), // 4 hours
    ],

    /*
    |--------------------------------------------------------------------------
    | Key Storage
    |--------------------------------------------------------------------------
    |
    | Configure where the encryption keys are stored.
    | Note: Using 'cache' is only suitable for development environments.
    | For production, consider more secure options like database or secure file storage.
    |
    */
    'storage' => [
        'driver' => env('ENCRYPTION_STORAGE_DRIVER', 'cache'),
        'cache_prefix' => 'crypto:',
        'cache_ttl' => 240, // minutes
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Options
    |--------------------------------------------------------------------------
    */
    'security' => [
        'enforce_tls' => env('ENFORCE_ENCRYPTION_TLS', true),
        'allow_insecure_mode' => env('ALLOW_INSECURE_ENCRYPTION', false),
    ],
];