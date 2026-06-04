<?php

namespace App\Models;

use App\Enums\SupportContactType;
use Illuminate\Database\Eloquent\Model;

class SupportContact extends Model
{
    protected $fillable = [
        'type',
        'username',
        'link',
        'qr_path',
        'enabled',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
            'sort_order' => 'integer',
            'type' => SupportContactType::class,
        ];
    }
}
