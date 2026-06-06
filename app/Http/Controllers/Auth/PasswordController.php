<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\UserPasswordService;
use App\Support\PasswordRules;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PasswordController extends Controller
{
    public function __construct(
        private readonly UserPasswordService $passwords,
    ) {}
    /**
     * Update the user's password.
     */
    public function update(Request $request): RedirectResponse
    {
        if ($request->user()->isAdmin()) {
            return redirect()
                ->route('admin.settings.security.edit')
                ->with('error', 'Administrator passwords must be changed from Admin → Security.');
        }

        $validated = $request->validateWithBag('updatePassword', [
            'current_password' => ['required', 'current_password'],
            'password' => PasswordRules::validationRules(),
        ]);

        $this->passwords->updatePassword(
            $request->user(),
            $validated['password'],
            $validated['current_password'],
        );

        return back()->with('status', 'password-updated');
    }
}
