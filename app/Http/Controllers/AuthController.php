<?php

namespace App\Http\Controllers;

use App\Services\Demo\DemoSessionManager;
use App\Services\Demo\DemoSessionReverter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function logout(Request $request, DemoSessionManager $demoSessions, DemoSessionReverter $demoReverter)
    {
        $user = Auth::guard('web')->user();

        if ($demoSessions->isDemoAdmin($user)) {
            $demoSessions->endBySessionId($request->session()->getId(), $demoReverter);
        }

        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Logged out successfully']);
        }

        return redirect('/login');
    }
}
