<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AdminPasswordService;
use App\Services\AuthSecurityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;

class PasswordResetLinkController extends Controller
{
    public function __construct(
        private readonly AdminPasswordService $adminPasswords,
    ) {}

    /**
     * Display the password reset link request view.
     */
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    /**
     * Handle an incoming password reset link request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $email = $request->string('email')->lower()->toString();
        $user = User::query()->where('email', $email)->first();

        if ($user?->isAdmin() && ! $this->adminPasswords->passwordResetAllowed($user)) {
            AuthSecurityLogger::log('admin_password_reset_blocked', [
                'email' => $email,
                'reason' => $this->adminPasswords->passwordResetEnabled() ? 'cooldown' : 'disabled',
            ]);

            // Do not reveal that the account exists or that reset is blocked.
            return back()->with('status', __(Password::RESET_LINK_SENT));
        }

        $status = Password::sendResetLink(
            $request->only('email')
        );

        if ($status === Password::RESET_LINK_SENT) {
            $event = $user?->isAdmin()
                ? 'admin_password_reset_requested'
                : 'password_reset_requested';

            AuthSecurityLogger::log($event, [
                'email' => $email,
            ]);

            return back()->with('status', __($status));
        }

        AuthSecurityLogger::log('password_reset_request_failed', [
            'email' => $email,
            'status' => $status,
        ]);

        return back()->withInput($request->only('email'))
            ->withErrors(['email' => __($status)]);
    }
}
