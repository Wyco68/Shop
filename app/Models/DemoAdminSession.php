<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DemoAdminSession extends Model
{
    protected $fillable = [
        'session_id', 'user_id', 'started_at', 'expires_at', 'ended_at', 'reverted_at',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'expires_at' => 'datetime',
            'ended_at' => 'datetime',
            'reverted_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function changeLogs(): HasMany
    {
        return $this->hasMany(DemoChangeLog::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function isActive(): bool
    {
        return is_null($this->ended_at) && is_null($this->reverted_at);
    }
}
