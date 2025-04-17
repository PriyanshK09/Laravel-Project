<?php

namespace App\Services;

use App\Models\EncryptionKey;
use App\Models\User;
use phpseclib3\Crypt\AES;
use phpseclib3\Crypt\RSA;
use phpseclib3\Crypt\Random;
use Illuminate\Support\Facades\Log;

class EncryptionService
{
    /**
     * Generate a new symmetric key for a user.
     *
     * @param User $user
     * @param string $name
     * @param string $algorithm
     * @return EncryptionKey
     */
    public function generateSymmetricKey(User $user, string $name = 'AES Key', string $algorithm = 'AES-256-CBC'): EncryptionKey
    {
        // Deactivate any existing symmetric keys
        $user->encryptionKeys()
            ->where('key_type', 'symmetric')
            ->where('active', true)
            ->update(['active' => false]);

        // Generate a new symmetric key
        $key = Random::string(32); // 256 bits
        $iv = Random::string(16);  // 128 bits initialization vector

        // Store the key in the database
        return EncryptionKey::create([
            'user_id' => $user->id,
            'name' => $name,
            'key_type' => 'symmetric',
            'key_data' => base64_encode($key),
            'initialization_vector' => base64_encode($iv),
            'algorithm' => $algorithm,
            'active' => true,
        ]);
    }

    /**
     * Generate a new asymmetric key pair for a user.
     *
     * @param User $user
     * @param string $name
     * @return array
     */
    public function generateAsymmetricKeyPair(User $user, string $name = 'RSA Key Pair'): array
    {
        // Deactivate any existing asymmetric keys
        $user->encryptionKeys()
            ->whereIn('key_type', ['public', 'private'])
            ->where('active', true)
            ->update(['active' => false]);

        // Generate RSA key pair
        $rsa = RSA::createKey(2048);
        $privateKey = $rsa->toString('PKCS8');
        $publicKey = $rsa->getPublicKey()->toString('PKCS8');

        // Store the keys in the database
        $public = EncryptionKey::create([
            'user_id' => $user->id,
            'name' => $name . ' (Public)',
            'key_type' => 'public',
            'key_data' => base64_encode($publicKey),
            'active' => true,
        ]);

        $private = EncryptionKey::create([
            'user_id' => $user->id,
            'name' => $name . ' (Private)',
            'key_type' => 'private',
            'key_data' => base64_encode($privateKey),
            'active' => true,
        ]);

        return [
            'public' => $public,
            'private' => $private
        ];
    }

    /**
     * Encrypt data using symmetric encryption.
     *
     * @param string $data
     * @param EncryptionKey $key
     * @return string|null
     */
    public function encryptSymmetric(string $data, EncryptionKey $key): ?string
    {
        try {
            if (!$key->isSymmetric()) {
                throw new \InvalidArgumentException('Key must be a symmetric key');
            }

            $keyData = base64_decode($key->key_data);
            $iv = base64_decode($key->initialization_vector);

            $cipher = new AES('cbc');
            $cipher->setKey($keyData);
            $cipher->setIV($iv);

            $encrypted = $cipher->encrypt($data);

            return base64_encode($encrypted);
        } catch (\Exception $e) {
            Log::error('Symmetric encryption error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Decrypt data using symmetric encryption.
     *
     * @param string $encryptedData
     * @param EncryptionKey $key
     * @return string|null
     */
    public function decryptSymmetric(string $encryptedData, EncryptionKey $key): ?string
    {
        try {
            if (!$key->isSymmetric()) {
                throw new \InvalidArgumentException('Key must be a symmetric key');
            }

            $keyData = base64_decode($key->key_data);
            $iv = base64_decode($key->initialization_vector);
            $data = base64_decode($encryptedData);

            $cipher = new AES('cbc');
            $cipher->setKey($keyData);
            $cipher->setIV($iv);

            return $cipher->decrypt($data);
        } catch (\Exception $e) {
            Log::error('Symmetric decryption error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Encrypt data using asymmetric encryption (recipient's public key).
     *
     * @param string $data
     * @param EncryptionKey $publicKey
     * @return string|null
     */
    public function encryptAsymmetric(string $data, EncryptionKey $publicKey): ?string
    {
        try {
            if (!$publicKey->isPublic()) {
                throw new \InvalidArgumentException('Key must be a public key');
            }

            $keyData = base64_decode($publicKey->key_data);
            $rsa = RSA::loadPublicKey($keyData);
            $rsa->setEncryptionMode(RSA::ENCRYPTION_OAEP);

            $encrypted = $rsa->encrypt($data);

            return base64_encode($encrypted);
        } catch (\Exception $e) {
            Log::error('Asymmetric encryption error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Decrypt data using asymmetric encryption (user's private key).
     *
     * @param string $encryptedData
     * @param EncryptionKey $privateKey
     * @return string|null
     */
    public function decryptAsymmetric(string $encryptedData, EncryptionKey $privateKey): ?string
    {
        try {
            if (!$privateKey->isPrivate()) {
                throw new \InvalidArgumentException('Key must be a private key');
            }

            $keyData = base64_decode($privateKey->key_data);
            $rsa = RSA::loadPrivateKey($keyData);
            $rsa->setEncryptionMode(RSA::ENCRYPTION_OAEP);

            $data = base64_decode($encryptedData);
            return $rsa->decrypt($data);
        } catch (\Exception $e) {
            Log::error('Asymmetric decryption error: ' . $e->getMessage());
            return null;
        }
    }
}