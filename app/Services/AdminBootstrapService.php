<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class AdminBootstrapService
{
    public function adminExists(): bool
    {
        return User::hasAdmin();
    }

    /**
     * @param  array{name?: string, email: string, password: string}  $data
     */
    public function createAdmin(array $data): User
    {
        if ($this->adminExists()) {
            throw ValidationException::withMessages([
                'email' => ['An administrator account already exists.'],
            ]);
        }

        $name = $data['name'] ?? strstr($data['email'], '@', true) ?: 'Administrator';

        return User::query()->create([
            'name' => $name,
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => UserRole::Admin->value,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);
    }

    public static function passwordRules(): array
    {
        return ['required', 'confirmed', Password::min(12)->mixedCase()->numbers()->symbols()];
    }
}
