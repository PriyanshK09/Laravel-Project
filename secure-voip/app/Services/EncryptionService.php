<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;

class EncryptionService
{
    public static function generateRSAKeyPair(): array
    {
        $config = [
            "private_key_bits" => 2048,
            "private_key_type" => OPENSSL_KEYTYPE_RSA,
        ];

        $res = openssl_pkey_new($config);
        openssl_pkey_export($res, $privateKey);
        $publicKey = openssl_pkey_get_details($res)['key'];

        return [
            'public'  => $publicKey,
            'private' => $privateKey,
        ];
    }

    public static function encryptAESKeyWithPublicKey(string $aesKey, string $publicKey): string
    {
        openssl_public_encrypt($aesKey, $encrypted, $publicKey);
        return base64_encode($encrypted);
    }

    public static function decryptAESKeyWithPrivateKey(string $encryptedKey, string $privateKey): string
    {
        openssl_private_decrypt(base64_decode($encryptedKey), $decrypted, $privateKey);
        return $decrypted;
    }

    public static function encryptMessageAES(string $message, string $aesKey): string
    {
        $iv = random_bytes(16);
        $encrypted = openssl_encrypt($message, 'aes-256-cbc', $aesKey, 0, $iv);
        return base64_encode($iv . $encrypted);
    }

    public static function decryptMessageAES(string $encrypted, string $aesKey): string
    {
        $data = base64_decode($encrypted);
        $iv = substr($data, 0, 16);
        $cipher = substr($data, 16);
        return openssl_decrypt($cipher, 'aes-256-cbc', $aesKey, 0, $iv);
    }
}
