<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Services\UserPasswordService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function __construct(
        private readonly UserPasswordService $passwords,
    ) {}
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        if ($request->user()->isAdmin() && $request->filled('password')) {
            return Redirect::route('admin.settings.security.edit')
                ->with('error', 'Administrator passwords must be changed from Admin → Security.');
        }

        $plainPassword = $validated['password'] ?? null;
        $currentPassword = $validated['current_password'] ?? null;
        unset($validated['password'], $validated['current_password'], $validated['password_confirmation']);

        $request->user()->fill(collect($validated)->only([
            'name', 'email', 'phone_num', 'address',
        ])->all());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        if ($plainPassword && $currentPassword) {
            $this->passwords->updatePassword(
                $request->user(),
                $plainPassword,
                $currentPassword,
            );
        }

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        DB::transaction(function () use ($user, $request) {
            Auth::logout();

            $user->delete();

            $request->session()->invalidate();
            $request->session()->regenerateToken();
        });

        return Redirect::to('/');
    }
}
