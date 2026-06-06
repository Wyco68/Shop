<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuthSecurityLogger;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VerifyEmailController extends Controller
{
    /**
     * Mark the user's email address as verified via signed link (no login required).
     */
    public function __invoke(Request $request, int $id, string $hash): RedirectResponse
    {
        if (! $request->hasValidSignature()) {
            AuthSecurityLogger::log('invalid_verification_signature', [
                'user_id' => $id,
            ]);

            abort(403, 'This verification link is invalid or has expired.');
        }

        $user = User::query()->findOrFail($id);

        if (! hash_equals($hash, sha1($user->getEmailForVerification()))) {
            AuthSecurityLogger::log('invalid_verification_hash', [
                'user_id' => $user->id,
                'email' => $user->email,
            ]);

            abort(403, 'This verification link is invalid.');
        }

        if ($user->hasVerifiedEmail()) {
            return redirect()
                ->route('login')
                ->with('status', 'Your email is already verified. You can log in.');
        }

        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()
            ->intended($user->homeUrl().'?verified=1')
            ->with('status', 'Your email has been verified.');
    }
}
