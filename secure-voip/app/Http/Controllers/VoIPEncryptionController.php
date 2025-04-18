<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\EncryptionService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class VoIPEncryptionController extends Controller
{
    /**
     * Generate new encryption keys based on the method requested
     */
    public function generateKeys(Request $request)
    {
        $method = $request->method ?? 'aes';
        $userId = Auth::id();
        
        try {
            switch ($method) {
                case 'rsa':
                    $keys = EncryptionService::generateRSAKeyPair();
                    
                    // Store private key securely in server-side cache with user ID
                    Cache::put('user:'.$userId.':rsa_private_key', $keys['private'], now()->addHours(4));
                    
                    // Return only public key to client
                    return response()->json([
                        'success' => true,
                        'method' => 'rsa',
                        'public_key' => $keys['public'],
                        'info' => EncryptionService::getEncryptionInfo('rsa')
                    ]);
                    
                case 'aes':
                    // Generate AES key for symmetric encryption
                    $aesKey = EncryptionService::generateAESKey();
                    
                    // Store the binary key in cache as base64
                    $base64ForStorage = base64_encode($aesKey);
                    Cache::put('user:'.$userId.':aes_key', $base64ForStorage, now()->addHours(4));
                    
                    // Return base64-encoded key to client
                    return response()->json([
                        'success' => true,
                        'method' => 'aes',
                        'key' => $base64ForStorage,
                        'info' => EncryptionService::getEncryptionInfo('aes')
                    ]);
                    
                case 'none':
                    // Clear any existing keys for security
                    Cache::forget('user:'.$userId.':aes_key');
                    Cache::forget('user:'.$userId.':rsa_private_key');
                    
                    return response()->json([
                        'success' => true,
                        'method' => 'none',
                        'info' => EncryptionService::getEncryptionInfo('none')
                    ]);
                    
                default:
                    return response()->json([
                        'success' => false,
                        'error' => 'Unsupported encryption method'
                    ], 400);
            }
        } catch (\Exception $e) {
            Log::error('Encryption key generation failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Failed to generate encryption keys: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Exchange keys between users initiating a call
     */
    public function exchangeKeys(Request $request)
    {
        $userId = Auth::id();
        $peerPublicKey = $request->peer_public_key;
        $method = $request->method ?? 'aes';
        
        try {
            if ($method === 'rsa') {
                // For RSA, we already sent our public key, now store peer's public key
                Cache::put('user:'.$userId.':peer_public_key', $peerPublicKey, now()->addHours(1));
                
                return response()->json([
                    'success' => true,
                    'method' => 'rsa',
                    'status' => 'Public key received'
                ]);
            } elseif ($method === 'aes') {
                // For AES hybrid encryption approach
                if (!$peerPublicKey) {
                    return response()->json([
                        'success' => false, 
                        'error' => 'Peer public key is required'
                    ], 400);
                }
                
                // Get our AES key from cache (stored as base64)
                $base64AesKey = Cache::get('user:'.$userId.':aes_key');
                
                if (!$base64AesKey) {
                    // Generate a new one if not found
                    $aesKey = EncryptionService::generateAESKey();
                    $base64AesKey = base64_encode($aesKey);
                    Cache::put('user:'.$userId.':aes_key', $base64AesKey, now()->addHours(4));
                }
                
                // Decode it back to binary for encryption operations
                $binaryAesKey = base64_decode($base64AesKey);
                
                // Encrypt the binary AES key with peer's public key
                $encryptedKey = EncryptionService::encryptAESKeyWithPublicKey($binaryAesKey, $peerPublicKey);
                
                return response()->json([
                    'success' => true,
                    'method' => 'aes',
                    'encrypted_key' => $encryptedKey
                ]);
            }
            
            return response()->json([
                'success' => false,
                'error' => 'Invalid exchange method'
            ], 400);
            
        } catch (\Exception $e) {
            Log::error('Key exchange failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Key exchange failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store received encrypted AES key
     */
    public function storeEncryptedAESKey(Request $request)
    {
        $userId = Auth::id();
        $encryptedKey = $request->encrypted_key;
        
        try {
            // Get our private key
            $privateKey = Cache::get('user:'.$userId.':rsa_private_key');
            
            if (!$privateKey) {
                return response()->json([
                    'success' => false,
                    'error' => 'Private key not found. Please refresh your encryption keys.'
                ], 404);
            }
            
            // Decrypt the AES key with our private key
            $decryptedAesKey = EncryptionService::decryptAESKeyWithPrivateKey($encryptedKey, $privateKey);
            
            // Store the AES key for use in real-time communication (as base64)
            $base64DecryptedKey = base64_encode($decryptedAesKey);
            Cache::put('user:'.$userId.':session_aes_key', $base64DecryptedKey, now()->addHours(1));
            
            return response()->json([
                'success' => true,
                'status' => 'AES key stored successfully'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Storing encrypted AES key failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Failed to store encrypted key: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get the current encryption status
     */
    public function getEncryptionStatus(Request $request)
    {
        $method = $request->method ?? 'none';
        return response()->json([
            'success' => true,
            'info' => EncryptionService::getEncryptionInfo($method),
            'method' => $method
        ]);
    }
    
    /**
     * Test OpenSSL functionality
     */
    public function testOpenSSL()
    {
        try {
            // Test OpenSSL functions
            $testMessage = "OpenSSL test message";
            
            // Test symmetric encryption
            $aesKey = EncryptionService::generateAESKey();
            $encryptedMessage = EncryptionService::encryptMessageAES($testMessage, $aesKey);
            $decryptedMessage = EncryptionService::decryptMessageAES($encryptedMessage, $aesKey);
            $aesWorking = ($decryptedMessage === $testMessage);
            
            // Test key generation
            $keys = EncryptionService::generateRSAKeyPair();
            $rsaWorking = (!empty($keys['public']) && !empty($keys['private']));
            
            return response()->json([
                'success' => true,
                'openssl_version' => OPENSSL_VERSION_TEXT,
                'aes_encryption_working' => $aesWorking,
                'rsa_key_generation_working' => $rsaWorking
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'OpenSSL test failed: ' . $e->getMessage(),
                'openssl_version' => OPENSSL_VERSION_TEXT
            ], 500);
        }
    }
}