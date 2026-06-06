<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateAdminPasswordRequest;
use App\Services\AdminPasswordService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SecuritySettingsController extends Controller
{
    public function __construct(
        private readonly AdminPasswordService $passwords,
    ) {}

    public function edit(): View
    {
        $admin = auth()->user();

        return view('admin.settings.security', [
            'webChangeEnabled' => $this->passwords->webChangeEnabled(),
            'passwordResetEnabled' => $this->passwords->passwordResetEnabled(),
            'passwordResetAllowed' => $this->passwords->passwordResetAllowed($admin),
            'cooldownEndsAt' => $this->passwords->cooldownEndsAt($admin),
            'cooldownMinutes' => config('admin.password.change_cooldown_minutes'),
        ]);
    }

    public function updatePassword(UpdateAdminPasswordRequest $request): RedirectResponse
    {
        $this->passwords->changePasswordForAuthenticatedAdmin(
            $request->user(),
            $request->string('current_password')->toString(),
            $request->string('password')->toString(),
        );

        return redirect()
            ->route('admin.settings.security.edit')
            ->with('status', 'admin-password-updated');
    }
}
