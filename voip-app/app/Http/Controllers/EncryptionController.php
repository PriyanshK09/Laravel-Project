<?php

namespace App\Http\Controllers;

use App\Models\EncryptionKey;
use App\Services\EncryptionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EncryptionController extends Controller
{
    protected $encryptionService;

    /**
     * Create a new controller instance.
     */
    public function __construct(EncryptionService $encryptionService)
    {
        $this->middleware('auth');
        $this->encryptionService = $encryptionService;
    }

    /**
     * Display a listing of the encryption keys.
     */
    public function index()
    {
        $user = Auth::user();
        
        $symmetricKeys = EncryptionKey::where('user_id', $user->id)
            ->where('key_type', 'symmetric')
            ->orderBy('created_at', 'desc')
            ->get();
            
        $asymmetricKeys = EncryptionKey::where('user_id', $user->id)
            ->whereIn('key_type', ['public', 'private'])
            ->orderBy('created_at', 'desc')
            ->get();
        
        return view('encryption.index', compact('symmetricKeys', 'asymmetricKeys'));
    }

    /**
     * Show the form for creating a new encryption key.
     */
    public function create()
    {
        return view('encryption.create');
    }

    /**
     * Store a newly created encryption key.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'key_type' => 'required|in:symmetric,asymmetric',
        ]);
        
        $user = Auth::user();
        $name = $request->name;
        
        if ($request->key_type === 'symmetric') {
            $key = $this->encryptionService->generateSymmetricKey($user, $name);
            $message = 'Symmetric key created successfully.';
        } else {
            $keys = $this->encryptionService->generateAsymmetricKeyPair($user, $name);
            $key = $keys['public']; // Reference the public key for the redirect
            $message = 'Asymmetric key pair created successfully.';
        }
        
        return redirect()->route('encryption.show', $key->id)->with('status', [
            'type' => 'success',
            'message' => $message
        ]);
    }

    /**
     * Display the specified encryption key.
     */
    public function show(string $id)
    {
        $key = EncryptionKey::findOrFail($id);
        
        // Security check - users can only view their own keys
        if ($key->user_id !== Auth::id()) {
            abort(403, 'Unauthorized action.');
        }
        
        // If it's a private key, also get the corresponding public key
        $relatedKey = null;
        if ($key->key_type === 'private') {
            $relatedKey = EncryptionKey::where('user_id', Auth::id())
                ->where('key_type', 'public')
                ->where('created_at', $key->created_at)
                ->first();
        } elseif ($key->key_type === 'public') {
            $relatedKey = EncryptionKey::where('user_id', Auth::id())
                ->where('key_type', 'private')
                ->where('created_at', $key->created_at)
                ->first();
        }
        
        return view('encryption.show', compact('key', 'relatedKey'));
    }

    /**
     * Show the form for editing the encryption key name.
     */
    public function edit(string $id)
    {
        $key = EncryptionKey::findOrFail($id);
        
        // Security check - users can only edit their own keys
        if ($key->user_id !== Auth::id()) {
            abort(403, 'Unauthorized action.');
        }
        
        return view('encryption.edit', compact('key'));
    }

    /**
     * Update the encryption key name.
     */
    public function update(Request $request, string $id)
    {
        $key = EncryptionKey::findOrFail($id);
        
        // Security check - users can only update their own keys
        if ($key->user_id !== Auth::id()) {
            abort(403, 'Unauthorized action.');
        }
        
        $request->validate([
            'name' => 'required|string|max:255',
        ]);
        
        $key->name = $request->name;
        $key->save();
        
        return redirect()->route('encryption.show', $key->id)->with('status', [
            'type' => 'success',
            'message' => 'Key name updated successfully.'
        ]);
    }

    /**
     * Remove the specified encryption key.
     */
    public function destroy(string $id)
    {
        $key = EncryptionKey::findOrFail($id);
        
        // Security check - users can only delete their own keys
        if ($key->user_id !== Auth::id()) {
            abort(403, 'Unauthorized action.');
        }
        
        // If it's part of a key pair, delete both keys
        if (in_array($key->key_type, ['public', 'private'])) {
            $keyType = $key->key_type === 'public' ? 'private' : 'public';
            $pairedKey = EncryptionKey::where('user_id', Auth::id())
                ->where('key_type', $keyType)
                ->where('created_at', $key->created_at)
                ->first();
            
            if ($pairedKey) {
                $pairedKey->delete();
            }
        }
        
        $key->delete();
        
        return redirect()->route('encryption.index')->with('status', [
            'type' => 'success',
            'message' => 'Encryption key deleted successfully.'
        ]);
    }

    /**
     * Set a key as active.
     */
    public function setActive(Request $request, string $id)
    {
        $key = EncryptionKey::findOrFail($id);
        
        // Security check - users can only modify their own keys
        if ($key->user_id !== Auth::id()) {
            abort(403, 'Unauthorized action.');
        }
        
        // Deactivate all other keys of the same type
        EncryptionKey::where('user_id', Auth::id())
            ->where('key_type', $key->key_type)
            ->update(['active' => false]);
        
        // If it's part of a key pair, also activate the other key
        if (in_array($key->key_type, ['public', 'private'])) {
            $keyType = $key->key_type === 'public' ? 'private' : 'public';
            $pairedKey = EncryptionKey::where('user_id', Auth::id())
                ->where('key_type', $keyType)
                ->where('created_at', $key->created_at)
                ->first();
            
            if ($pairedKey) {
                $pairedKey->active = true;
                $pairedKey->save();
            }
        }
        
        // Activate the current key
        $key->active = true;
        $key->save();
        
        return redirect()->route('encryption.index')->with('status', [
            'type' => 'success',
            'message' => 'Key set as active successfully.'
        ]);
    }
}