<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuthSecurityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ResendVerificationEmailController extends Controller
{
    /**
     * Resend verification email for guests (e.g. after blocked login).
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'string', 'email'],
        ]);

        $user = User::query()->where('email', $request->string('email')->lower())->first();

        if ($user && ! $user->hasVerifiedEmail()) {
            $user->sendEmailVerificationNotification();
            AuthSecurityLogger::log('verification_email_resent', ['email' => $user->email]);
        }

        return back()->with('status', 'verification-link-sent');
    }
}
