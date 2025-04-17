<?php

use App\Http\Controllers\CallController;
use App\Http\Controllers\EncryptionController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Authentication routes
Auth::routes();

// All routes that require authentication
Route::middleware('auth')->group(function () {
    Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');
    
    // Resource routes for calls
    Route::resource('calls', CallController::class);
    
    // Additional routes for encryption functionality
    Route::post('/calls/{id}/exchange-keys', [CallController::class, 'exchangeKeys'])->name('calls.exchange-keys');
    Route::post('/calls/encrypt', [CallController::class, 'encrypt'])->name('calls.encrypt');
    Route::post('/calls/decrypt', [CallController::class, 'decrypt'])->name('calls.decrypt');
    
    // Encryption key management routes
    Route::resource('encryption', EncryptionController::class);
    Route::put('/encryption/{id}/set-active', [EncryptionController::class, 'setActive'])->name('encryption.setActive');
    
    // Profile routes
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::get('/profile/security', [ProfileController::class, 'security'])->name('profile.security');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password.update');
});
