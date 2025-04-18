<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

class EncryptionService
{
    // RSA key generation with improved error handling
    public static function generateRSAKeyPair(): array
    {
        // Load RSA config
        $rsaConfig = Config::get('openssl.rsa');
        
        // Use our custom OpenSSL config file
        $opensslConfigPath = storage_path('app/openssl.cnf');
        
        $config = [
            "private_key_bits" => $rsaConfig['key_bits'],
            "private_key_type" => $rsaConfig['key_type'],
            "digest_alg" => "sha256",
            // Use our custom config file if it exists, otherwise use PHP's default
            "config" => file_exists($opensslConfigPath) ? $opensslConfigPath : php_ini_loaded_file()
        ];
        
        // Add error handling
        $res = openssl_pkey_new($config);
        if ($res === false) {
            throw new \Exception("RSA key generation failed: " . openssl_error_string());
        }
        
        $success = openssl_pkey_export($res, $privateKey, null, $config);
        if ($success === false) {
            throw new \Exception("RSA key export failed: " . openssl_error_string());
        }
        
        $keyDetails = openssl_pkey_get_details($res);
        if ($keyDetails === false) {
            throw new \Exception("Could not get RSA key details: " . openssl_error_string());
        }
        
        $publicKey = $keyDetails['key'];
        
        return [
            'public'  => $publicKey,
            'private' => $privateKey,
        ];
    }

    // RSA direct encryption (for small payloads only)
    public static function encryptRSA(string $message, string $publicKey): string
    {
        $padding = Config::get('openssl.rsa.padding', OPENSSL_PKCS1_PADDING);
        openssl_public_encrypt($message, $encrypted, $publicKey, $padding);
        return base64_encode($encrypted);
    }

    // RSA direct decryption
    public static function decryptRSA(string $encrypted, string $privateKey): string
    {
        $padding = Config::get('openssl.rsa.padding', OPENSSL_PKCS1_PADDING);
        $data = base64_decode($encrypted);
        openssl_private_decrypt($data, $decrypted, $privateKey, $padding);
        return $decrypted;
    }

    // Hybrid encryption for VoIP - encrypt AES key with RSA
    public static function encryptAESKeyWithPublicKey(string $aesKey, string $publicKey): string
    {
        $padding = Config::get('openssl.rsa.padding', OPENSSL_PKCS1_PADDING);
        openssl_public_encrypt($aesKey, $encrypted, $publicKey, $padding);
        return base64_encode($encrypted);
    }

    // Decrypt AES key using private RSA key
    public static function decryptAESKeyWithPrivateKey(string $encryptedKey, string $privateKey): string
    {
        $padding = Config::get('openssl.rsa.padding', OPENSSL_PKCS1_PADDING);
        openssl_private_decrypt(base64_decode($encryptedKey), $decrypted, $privateKey, $padding);
        return $decrypted;
    }

    // Generate a strong AES key for symmetric encryption
    public static function generateAESKey(int $length = null): string
    {
        if ($length === null) {
            $length = Config::get('openssl.aes.key_length', 32);
        }
        
        try {
            return random_bytes($length);
        } catch (\Exception $e) {
            Log::warning("Failed to generate random bytes, falling back to openssl_random_pseudo_bytes: " . $e->getMessage());
            return openssl_random_pseudo_bytes($length);
        }
    }

    // AES encryption with random IV
    public static function encryptMessageAES(string $message, string $aesKey): string
    {
        $ivLength = Config::get('openssl.aes.iv_length', 16);
        $cipher = Config::get('openssl.aes.cipher', 'aes-256-cbc');
        
        $iv = random_bytes($ivLength);
        $encrypted = openssl_encrypt(
            $message, 
            $cipher, 
            $aesKey, 
            OPENSSL_RAW_DATA, 
            $iv
        );
        
        if ($encrypted === false) {
            Log::error('AES encryption failed: ' . openssl_error_string());
            throw new \Exception('AES encryption failed: ' . openssl_error_string());
        }
        
        // Return IV + encrypted data
        return base64_encode($iv . $encrypted);
    }

    // AES decryption
    public static function decryptMessageAES(string $encrypted, string $aesKey): string
    {
        $ivLength = Config::get('openssl.aes.iv_length', 16);
        $cipher = Config::get('openssl.aes.cipher', 'aes-256-cbc');
        
        $data = base64_decode($encrypted);
        $iv = substr($data, 0, $ivLength);
        $cipherText = substr($data, $ivLength);
        
        $decrypted = openssl_decrypt(
            $cipherText, 
            $cipher, 
            $aesKey, 
            OPENSSL_RAW_DATA, 
            $iv
        );
        
        if ($decrypted === false) {
            Log::error('AES decryption failed: ' . openssl_error_string());
            throw new \Exception('AES decryption failed: ' . openssl_error_string());
        }
        
        return $decrypted;
    }
    
    // Verify if keys are valid
    public static function verifyRSAKeyPair(string $publicKey, string $privateKey): bool
    {
        try {
            $testMessage = "Test encryption message for key validation";
            $encrypted = self::encryptRSA($testMessage, $publicKey);
            $decrypted = self::decryptRSA($encrypted, $privateKey);
            return $decrypted === $testMessage;
        } catch (\Exception $e) {
            Log::error('RSA key pair verification failed: ' . $e->getMessage());
            return false;
        }
    }
    
    // Get encryption status info
    public static function getEncryptionInfo(string $encryptionMethod): array
    {
        switch($encryptionMethod) {
            case 'aes':
                $cipher = Config::get('openssl.aes.cipher', 'aes-256-cbc');
                $keyLength = Config::get('openssl.aes.key_length', 32) * 8; // bits
                return [
                    'name' => strtoupper(str_replace('-', '-', $cipher)),
                    'type' => 'Symmetric',
                    'keySize' => $keyLength . ' bits',
                    'secure' => true,
                    'description' => 'End-to-end symmetric encryption',
                ];
            case 'rsa':
                $keyBits = Config::get('openssl.rsa.key_bits', 2048);
                return [
                    'name' => 'RSA-' . $keyBits,
                    'type' => 'Asymmetric',
                    'keySize' => $keyBits . ' bits',
                    'secure' => true,
                    'description' => 'Public-key asymmetric encryption',
                ];
            case 'none':
                return [
                    'name' => 'None',
                    'type' => 'Unencrypted',
                    'keySize' => '0 bits',
                    'secure' => false,
                    'description' => 'No encryption (not recommended)',
                ];
            default:
                return [
                    'name' => 'Unknown',
                    'type' => 'Unknown',
                    'keySize' => 'Unknown',
                    'secure' => false,
                    'description' => 'Unknown encryption method',
                ];
        }
    }
    
    /**
     * Encrypt VoIP data (audio/video packets)
     * 
     * @param string $data Binary data to encrypt
     * @param string $base64Key Base64-encoded encryption key
     * @param string $method Encryption method
     * @return string Encrypted data
     */
    public static function encryptVoIPData(string $data, string $base64Key, string $method = 'aes'): string
    {
        if ($method === 'none') {
            return $data;
        }
        
        try {
            if ($method === 'aes') {
                // The key comes in as base64, decode it first
                $binaryKey = base64_decode($base64Key);
                return self::encryptMessageAES($data, $binaryKey);
            } elseif ($method === 'rsa') {
                // RSA is not suitable for large data encryption
                // In practice, RSA should only be used to encrypt the AES key
                throw new \Exception('RSA is not suitable for direct VoIP data encryption');
            }
        } catch (\Exception $e) {
            Log::error('VoIP data encryption failed: ' . $e->getMessage());
            // Return unencrypted data if encryption fails
            return $data;
        }
    }
    
    /**
     * Decrypt VoIP data (audio/video packets)
     * 
     * @param string $encryptedData Encrypted binary data
     * @param string $base64Key Base64-encoded decryption key
     * @param string $method Decryption method
     * @return string Decrypted data
     */
    public static function decryptVoIPData(string $encryptedData, string $base64Key, string $method = 'aes'): string
    {
        if ($method === 'none') {
            return $encryptedData;
        }
        
        try {
            if ($method === 'aes') {
                // The key comes in as base64, decode it first
                $binaryKey = base64_decode($base64Key);
                return self::decryptMessageAES($encryptedData, $binaryKey);
            } elseif ($method === 'rsa') {
                throw new \Exception('RSA is not suitable for direct VoIP data decryption');
            }
        } catch (\Exception $e) {
            Log::error('VoIP data decryption failed: ' . $e->getMessage());
            // Return original data if decryption fails
            return $encryptedData;
        }
    }
}
