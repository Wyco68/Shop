<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\PasswordRules;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => PasswordRules::validationRules(),
            'phone_num' => ['required', 'string', 'max:20'],
            'address' => ['required', 'string', 'max:500'],
            'role' => ['prohibited'],
            'is_admin' => ['prohibited'],
            'is_active' => ['prohibited'],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => $request->password,
            'phone_num' => $request->phone_num,
            'address' => $request->address,
        ]);

        event(new Registered($user));

        $user->sendEmailVerificationNotification();

        return redirect()
            ->route('register.success')
            ->with('email', $user->email);
    }
}
