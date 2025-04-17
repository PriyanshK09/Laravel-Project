<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EncryptionKey extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'key_type',
        'key_data',
        'initialization_vector',
        'algorithm',
        'active',
        'expires_at',
    ];

    protected $casts = [
        'active' => 'boolean',
        'expires_at' => 'datetime',
    ];

    /**
     * Get the user that owns the encryption key.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope a query to only include active keys.
     */
    public function scopeActive($query)
    {
        return $query->where('active', true)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
    }

    /**
     * Check if the key is a symmetric key.
     */
    public function isSymmetric(): bool
    {
        return $this->key_type === 'symmetric';
    }

    /**
     * Check if the key is a public key.
     */
    public function isPublic(): bool
    {
        return $this->key_type === 'public';
    }

    /**
     * Check if the key is a private key.
     */
    public function isPrivate(): bool
    {
        return $this->key_type === 'private';
    }
}
