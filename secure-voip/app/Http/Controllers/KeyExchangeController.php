<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\EncryptionService;
use Illuminate\Support\Facades\Cache;

class KeyExchangeController extends Controller
{
    public function generateKeys()
    {
        $keys = EncryptionService::generateRSAKeyPair();

        // Store user's private key securely (could use DB or cache per user session)
        Cache::put('private_key_' . auth()->id(), $keys['private'], now()->addMinutes(30));

        return response()->json(['public_key' => $keys['public']]);
    }

    public function receiveEncryptedAES(Request $request)
    {
        $privateKey = Cache::get('private_key_' . auth()->id());

        $aesKey = EncryptionService::decryptAESKeyWithPrivateKey(
            $request->encrypted_aes_key,
            $privateKey
        );

        Cache::put('aes_key_' . auth()->id(), $aesKey, now()->addMinutes(30));

        return response()->json(['status' => 'AES key stored']);
    }
}
