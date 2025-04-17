<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Call extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'caller_id',
        'recipient_id',
        'session_id',
        'encryption_method',
        'symmetric_key',
        'status',
        'started_at',
        'ended_at',
        'call_metadata',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'call_metadata' => 'array',
    ];

    /**
     * Get the caller associated with the call.
     */
    public function caller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'caller_id');
    }

    /**
     * Get the recipient associated with the call.
     */
    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_id');
    }

    /**
     * Get the duration of the call in seconds.
     *
     * @return int|null
     */
    public function getDuration()
    {
        if (!$this->started_at || !$this->ended_at) {
            return null;
        }

        return $this->ended_at->diffInSeconds($this->started_at);
    }

    /**
     * Check if the call is currently active.
     *
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->status === 'initiated' || $this->status === 'connected';
    }
}
