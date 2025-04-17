<?php

namespace App\Http\Controllers;

use App\Models\Call;
use App\Models\User;
use App\Models\EncryptionKey;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        $user = Auth::user();
        
        // Get users for call initiation (excluding the current user)
        $users = User::where('id', '!=', $user->id)->get();
        
        // Get recent encryption keys
        $recentKeys = EncryptionKey::where('user_id', $user->id)
            ->latest()
            ->take(3)
            ->get();
        
        // Get recent calls with error handling for relationships
        try {
            $recentCalls = Call::where(function($query) use ($user) {
                    $query->where('caller_id', $user->id)
                        ->orWhere('recipient_id', $user->id);
                })
                ->with(['caller', 'recipient'])
                ->latest()
                ->take(5)
                ->get()
                ->map(function($call) use ($user) {
                    // Make sure both caller and recipient exist
                    if ($call->caller && $call->recipient) {
                        $call->participant = ($call->caller_id == $user->id) 
                            ? $call->recipient 
                            : $call->caller;
                            
                        $call->duration_formatted = $call->getDuration() 
                            ? gmdate('H:i:s', $call->getDuration()) 
                            : '--:--:--';
                    } else {
                        // Handle case where a relationship might be missing
                        $call->participant = null;
                        $call->duration_formatted = '--:--:--';
                    }
                    
                    return $call;
                });
        } catch (\Exception $e) {
            // If there's an error with calls, return an empty collection
            $recentCalls = collect([]);
        }
            
        // Get encryption keys by type with error handling
        try {
            $symmetricKeys = EncryptionKey::where('user_id', $user->id)
                ->where('key_type', 'symmetric')
                ->latest()
                ->get();
                
            $asymmetricKeys = EncryptionKey::where('user_id', $user->id)
                ->whereIn('key_type', ['public', 'private'])
                ->where('key_type', 'public')  // Only show public keys in the dropdown
                ->latest()
                ->get();
        } catch (\Exception $e) {
            $symmetricKeys = collect([]);
            $asymmetricKeys = collect([]);
        }

        return view('home', compact(
            'users',
            'recentKeys',
            'recentCalls',
            'symmetricKeys',
            'asymmetricKeys'
        ));
    }
}
