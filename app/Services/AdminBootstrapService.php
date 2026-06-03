<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class AdminBootstrapService
{
    public function adminExists(): bool
    {
        return User::hasAdmin();
    }

    /**
     * @param  array{name?: string, store_name: string, email: string, password: string}  $data
     */
    public function createAdmin(array $data): User
    {
        if ($this->adminExists()) {
            throw ValidationException::withMessages([
                'email' => ['An administrator account already exists.'],
            ]);
        }

        $name = $data['name'] ?? strstr($data['email'], '@', true) ?: 'Administrator';

        app(StoreSettingsService::class)->initializeStoreName($data['store_name']);

        $user = User::query()->create([
            'name' => $name,
            'email' => $data['email'],
            'password' => $data['password'],
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        // Role is not mass-assignable; set explicitly for bootstrap only.
        $user->forceFill(['role' => UserRole::Admin->value])->save();

        return $user->refresh();
    }

    public static function passwordRules(): array
    {
        return ['required', 'confirmed', Password::min(12)->mixedCase()->numbers()->symbols()];
    }
}
