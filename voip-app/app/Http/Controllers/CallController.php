<?php

namespace App\Http\Controllers;

use App\Models\Call;
use App\Models\User;
use App\Services\EncryptionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class CallController extends Controller
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
     * Display a listing of the calls.
     */
    public function index()
    {
        $user = Auth::user();
        
        $calls = Call::where('caller_id', $user->id)
            ->orWhere('recipient_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->paginate(10);
        
        return view('calls.index', compact('calls'));
    }

    /**
     * Show the form for creating a new call.
     */
    public function create()
    {
        $users = User::where('id', '!=', Auth::id())->get();
        return view('calls.create', compact('users'));
    }

    /**
     * Initiate a new call.
     */
    public function store(Request $request)
    {
        $request->validate([
            'recipient_id' => 'required|exists:users,id',
            'encryption_method' => 'required|in:symmetric,asymmetric',
        ]);

        $caller = Auth::user();
        $recipient = User::findOrFail($request->recipient_id);
        $encryptionMethod = $request->encryption_method;
        
        $call = new Call();
        $call->caller_id = $caller->id;
        $call->recipient_id = $recipient->id;
        $call->session_id = Str::uuid();
        $call->encryption_method = $encryptionMethod;
        $call->status = 'initiated';
        
        // Handle encryption setup
        if ($encryptionMethod === 'symmetric') {
            // Get or create a symmetric key
            $symmetricKey = $caller->getActiveSymmetricKey();
            
            if (!$symmetricKey) {
                $symmetricKey = $this->encryptionService->generateSymmetricKey($caller);
            }
            
            // Store an encrypted version of the symmetric key
            $call->symmetric_key = $this->encryptionService->encryptSymmetric(
                json_encode([
                    'key' => $symmetricKey->key_data,
                    'iv' => $symmetricKey->initialization_vector
                ]),
                $symmetricKey
            );
        }
        
        $call->save();
        
        return redirect()->route('calls.show', $call->id)
            ->with('success', 'Call initiated. Waiting for recipient to connect.');
    }

    /**
     * Display the specified call and connect if needed.
     */
    public function show(string $id)
    {
        $call = Call::findOrFail($id);
        $user = Auth::user();
        
        // Check if user is part of this call
        if ($call->caller_id !== $user->id && $call->recipient_id !== $user->id) {
            return redirect()->route('calls.index')
                ->with('error', 'You are not authorized to view this call.');
        }
        
        $isRecipient = ($call->recipient_id === $user->id);
        $otherUser = $isRecipient ? $call->caller : $call->recipient;
        
        // Update call status if recipient is viewing
        if ($isRecipient && $call->status === 'initiated') {
            $call->status = 'connected';
            $call->started_at = now();
            $call->save();
        }
        
        return view('calls.show', compact('call', 'isRecipient', 'otherUser'));
    }

    /**
     * End a call.
     */
    public function update(Request $request, string $id)
    {
        $call = Call::findOrFail($id);
        $user = Auth::user();
        
        // Check if user is part of this call
        if ($call->caller_id !== $user->id && $call->recipient_id !== $user->id) {
            return redirect()->route('calls.index')
                ->with('error', 'You are not authorized to update this call.');
        }
        
        if ($request->action === 'end') {
            $call->status = 'completed';
            $call->ended_at = now();
            $call->save();
            
            return redirect()->route('calls.index')
                ->with('success', 'Call ended successfully.');
        }
        
        return back()->with('error', 'Invalid action specified.');
    }

    /**
     * Remove call history (soft delete).
     */
    public function destroy(string $id)
    {
        $call = Call::findOrFail($id);
        $user = Auth::user();
        
        // Check if user is part of this call
        if ($call->caller_id !== $user->id && $call->recipient_id !== $user->id) {
            return redirect()->route('calls.index')
                ->with('error', 'You are not authorized to delete this call.');
        }
        
        $call->delete();
        
        return redirect()->route('calls.index')
            ->with('success', 'Call record removed from your history.');
    }
    
    /**
     * Exchange encryption keys for secure communication.
     */
    public function exchangeKeys(Request $request, string $id)
    {
        $call = Call::findOrFail($id);
        $user = Auth::user();
        
        // Check if user is part of this call
        if ($call->caller_id !== $user->id && $call->recipient_id !== $user->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        
        $otherUserId = ($call->caller_id === $user->id) ? $call->recipient_id : $call->caller_id;
        $otherUser = User::findOrFail($otherUserId);
        
        // Handle key exchange based on encryption method
        if ($call->encryption_method === 'symmetric') {
            // For symmetric, we just confirm the key is available
            $symmetricKey = $user->getActiveSymmetricKey();
            if (!$symmetricKey) {
                $symmetricKey = $this->encryptionService->generateSymmetricKey($user);
            }
            
            return response()->json([
                'success' => true,
                'encryption_method' => 'symmetric',
                'key_status' => 'available'
            ]);
        } else {
            // For asymmetric, exchange public keys
            $userPublicKey = $user->getActivePublicKey();
            if (!$userPublicKey) {
                $keys = $this->encryptionService->generateAsymmetricKeyPair($user);
                $userPublicKey = $keys['public'];
            }
            
            $otherUserPublicKey = $otherUser->getActivePublicKey();
            if (!$otherUserPublicKey) {
                return response()->json([
                    'error' => 'Recipient has no public key available'
                ], 400);
            }
            
            return response()->json([
                'success' => true,
                'encryption_method' => 'asymmetric',
                'public_key' => $otherUserPublicKey->key_data
            ]);
        }
    }
    
    /**
     * Encrypt a message for secure transmission.
     */
    public function encrypt(Request $request)
    {
        $request->validate([
            'call_id' => 'required|exists:calls,id',
            'message' => 'required|string',
        ]);
        
        $call = Call::findOrFail($request->call_id);
        $user = Auth::user();
        
        // Check if user is part of this call
        if ($call->caller_id !== $user->id && $call->recipient_id !== $user->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        
        $message = $request->message;
        $otherUserId = ($call->caller_id === $user->id) ? $call->recipient_id : $call->caller_id;
        $otherUser = User::findOrFail($otherUserId);
        
        if ($call->encryption_method === 'symmetric') {
            $symmetricKey = $user->getActiveSymmetricKey();
            if (!$symmetricKey) {
                return response()->json(['error' => 'No symmetric key found'], 400);
            }
            
            $encrypted = $this->encryptionService->encryptSymmetric($message, $symmetricKey);
        } else {
            $otherUserPublicKey = $otherUser->getActivePublicKey();
            if (!$otherUserPublicKey) {
                return response()->json(['error' => 'No public key found for recipient'], 400);
            }
            
            $encrypted = $this->encryptionService->encryptAsymmetric($message, $otherUserPublicKey);
        }
        
        if ($encrypted === null) {
            return response()->json(['error' => 'Encryption failed'], 500);
        }
        
        return response()->json([
            'success' => true,
            'encrypted_message' => $encrypted
        ]);
    }
    
    /**
     * Decrypt a received message.
     */
    public function decrypt(Request $request)
    {
        $request->validate([
            'call_id' => 'required|exists:calls,id',
            'encrypted_message' => 'required|string',
        ]);
        
        $call = Call::findOrFail($request->call_id);
        $user = Auth::user();
        
        // Check if user is part of this call
        if ($call->caller_id !== $user->id && $call->recipient_id !== $user->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        
        $encryptedMessage = $request->encrypted_message;
        
        if ($call->encryption_method === 'symmetric') {
            $symmetricKey = $user->getActiveSymmetricKey();
            if (!$symmetricKey) {
                return response()->json(['error' => 'No symmetric key found'], 400);
            }
            
            $decrypted = $this->encryptionService->decryptSymmetric($encryptedMessage, $symmetricKey);
        } else {
            $privateKey = $user->getActivePrivateKey();
            if (!$privateKey) {
                return response()->json(['error' => 'No private key found'], 400);
            }
            
            $decrypted = $this->encryptionService->decryptAsymmetric($encryptedMessage, $privateKey);
        }
        
        if ($decrypted === null) {
            return response()->json(['error' => 'Decryption failed'], 500);
        }
        
        return response()->json([
            'success' => true,
            'decrypted_message' => $decrypted
        ]);
    }
}
