<?php

namespace App\Http\Middleware;

use App\Services\AuthSecurityLogger;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IsAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()?->isAdmin()) {
            AuthSecurityLogger::log('admin_endpoint_denied', [
                'user_id' => $request->user()?->id,
                'email' => $request->user()?->email,
                'path' => $request->path(),
                'method' => $request->method(),
            ]);

            abort(403, 'Unauthorized. Admin access required.');
        }

        return $next($request);
    }
}
