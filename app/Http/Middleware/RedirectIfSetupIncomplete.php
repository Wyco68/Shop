<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectIfSetupIncomplete
{
    /**
     * @var array<int, string>
     */
    private const ALLOWED_PATHS = [
        'up',
        'build/*',
        'storage/*',
        'favicon.ico',
        'images/*',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        if (User::hasAdmin()) {
            return $next($request);
        }

        foreach (self::ALLOWED_PATHS as $pattern) {
            if ($request->is($pattern)) {
                return $next($request);
            }
        }

        abort(503, 'This store has not been configured yet. An administrator must run `php artisan store:setup` on the server.');
    }
}
