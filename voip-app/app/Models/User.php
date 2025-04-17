<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    /**
     * Get the calls initiated by the user.
     */
    public function initiatedCalls(): HasMany
    {
        return $this->hasMany(Call::class, 'caller_id');
    }

    /**
     * Get the calls received by the user.
     */
    public function receivedCalls(): HasMany
    {
        return $this->hasMany(Call::class, 'recipient_id');
    }

    /**
     * Get all encryption keys owned by the user.
     */
    public function encryptionKeys(): HasMany
    {
        return $this->hasMany(EncryptionKey::class);
    }

    /**
     * Get the active symmetric key for the user.
     *
     * @return EncryptionKey|null
     */
    public function getActiveSymmetricKey()
    {
        return $this->encryptionKeys()
            ->where('key_type', 'symmetric')
            ->where('active', true)
            ->latest()
            ->first();
    }

    /**
     * Get the active public key for the user.
     *
     * @return EncryptionKey|null
     */
    public function getActivePublicKey()
    {
        return $this->encryptionKeys()
            ->where('key_type', 'public')
            ->where('active', true)
            ->latest()
            ->first();
    }

    /**
     * Get the active private key for the user.
     *
     * @return EncryptionKey|null
     */
    public function getActivePrivateKey()
    {
        return $this->encryptionKeys()
            ->where('key_type', 'private')
            ->where('active', true)
            ->latest()
            ->first();
    }
}
