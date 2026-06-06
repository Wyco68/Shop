<?php

namespace Database\Factories;

use App\Enums\UserRole;
use App\Services\AdminBootstrapService;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('Password1!Secure'),
            'role' => UserRole::User->value,
            'is_active' => true,
            'remember_token' => Str::random(10),
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    public function admin(): static
    {
        return $this->afterCreating(function ($user) {
            AdminBootstrapService::withAdminGrant(function () use ($user) {
                $user->forceFill([
                    'role' => UserRole::Admin->value,
                    'email_verified_at' => now(),
                ])->save();
            });
        });
    }
}
