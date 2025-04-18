<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\KeyExchangeController;
use App\Http\Controllers\SignalController;
use App\Http\Controllers\VoIPEncryptionController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Auth routes - removed leading slashes
Route::post('login', [AuthController::class, 'login']);
Route::post('register', [AuthController::class, 'register']);
Route::get('users/{id}', [AuthController::class, 'getUserInfo']);

// Protected routes
Route::middleware('auth:api')->group(function () {
    Route::get('me', [AuthController::class, 'me']);
    
    // Key exchange routes
    Route::get('keys/generate', [KeyExchangeController::class, 'generateKeys']);
    Route::post('keys/receive-aes', [KeyExchangeController::class, 'receiveEncryptedAES']);
    
    // Signal routes
    Route::post('signal/send', [SignalController::class, 'sendSignal']);
    Route::get('signal/receive', [SignalController::class, 'receiveSignal']);
    
    // VoIP Encryption Routes
    Route::post('/encryption/generate', [VoIPEncryptionController::class, 'generateKeys']);
    Route::post('/encryption/exchange', [VoIPEncryptionController::class, 'exchangeKeys']);
    Route::post('/encryption/store-aes-key', [VoIPEncryptionController::class, 'storeEncryptedAESKey']);
    Route::get('/encryption/status', [VoIPEncryptionController::class, 'getEncryptionStatus']);
});
