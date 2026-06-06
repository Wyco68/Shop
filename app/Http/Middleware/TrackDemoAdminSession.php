<?php

namespace App\Http\Middleware;

use App\Services\Demo\DemoSessionManager;
use App\Services\Demo\DemoSessionReverter;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * Caps the shared/demo admin account at a fixed session length and undoes
 * every write it made once that session ends (here, on expiry — explicit
 * logout is handled in AuthController::logout). The owner account
 * (User::is_owner) is never subject to this.
 */
class TrackDemoAdminSession
{
    public function __construct(
        private readonly DemoSessionManager $sessions,
        private readonly DemoSessionReverter $reverter,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->sessions->enabled()) {
            return $next($request);
        }

        $user = Auth::guard('web')->user();

        if (! $this->sessions->isDemoAdmin($user)) {
            return $next($request);
        }

        $session = $this->sessions->startOrResume($request, $user);

        if ($session->isExpired()) {
            $this->sessions->expireAndRevert($session, $this->reverter);

            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->with(
                'status',
                'Your 30-minute demo session ended and every change you made has been reverted. Log back in to start a new one.'
            );
        }

        View::share('demoAdminSession', $session);

        return $next($request);
    }
}
