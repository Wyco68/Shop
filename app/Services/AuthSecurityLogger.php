<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class AuthSecurityLogger
{
    public static function log(string $event, array $context = []): void
    {
        Log::warning("Auth security: {$event}", array_merge([
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ], $context));
    }
}
