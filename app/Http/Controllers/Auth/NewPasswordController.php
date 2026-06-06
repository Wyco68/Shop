<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\PasswordChangedNotification;
use App\Services\AdminPasswordService;
use App\Services\AuthSecurityLogger;
use App\Support\PasswordRules;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\View\View;

class NewPasswordController extends Controller
{
    public function __construct(
        private readonly AdminPasswordService $adminPasswords,
    ) {}

    /**
     * Display the password reset view.
     */
    public function create(Request $request): View
    {
        return view('auth.reset-password', ['request' => $request]);
    }

    /**
     * Handle an incoming new password request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => PasswordRules::validationRules(),
        ]);

        $email = $request->string('email')->lower()->toString();
        $user = User::query()->where('email', $email)->first();

        if ($user?->isAdmin() && ! $this->adminPasswords->passwordResetAllowed($user)) {
            AuthSecurityLogger::log('admin_password_reset_blocked', [
                'email' => $email,
                'reason' => $this->adminPasswords->passwordResetEnabled() ? 'cooldown' : 'disabled',
                'stage' => 'submit',
            ]);

            return back()->withInput($request->only('email'))
                ->withErrors([
                    'email' => $this->adminPasswords->passwordResetEnabled()
                        ? 'Password reset is temporarily unavailable because your administrator password was changed recently. Use Admin → Security or try again later.'
                        : 'Administrator password reset via email is disabled. Use Admin → Security or CLI.',
                ]);
        }

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user) use ($request) {
                $user->forceFill([
                    'password' => Hash::make($request->password),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));

                if ($user->isAdmin()) {
                    $this->adminPasswords->recordPasswordReset($user);
                }

                Auth::logoutOtherDevices($request->password);

                $user->notify(new PasswordChangedNotification);
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            $event = $user?->isAdmin() ? 'admin_password_reset_success' : 'password_reset_success';

            AuthSecurityLogger::log($event, [
                'email' => $email,
            ]);

            return redirect()->route('login')->with('status', __($status));
        }

        AuthSecurityLogger::log('password_reset_failed', [
            'email' => $email,
            'status' => $status,
        ]);

        return back()->withInput($request->only('email'))
            ->withErrors(['email' => __($status)]);
    }
}
