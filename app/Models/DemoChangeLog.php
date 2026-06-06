<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DemoChangeLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'demo_admin_session_id', 'model_type', 'model_key', 'action', 'before', 'after',
    ];

    protected function casts(): array
    {
        return [
            'before' => 'array',
            'after' => 'array',
            'created_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $log) {
            $log->created_at ??= now();
        });
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(DemoAdminSession::class, 'demo_admin_session_id');
    }
}
