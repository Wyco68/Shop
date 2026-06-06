<?php

namespace App\Services;

use App\Enums\CurrencyPosition;
use App\Enums\UserRole;
use App\Models\User;
use App\Support\PasswordRules;
use Illuminate\Validation\ValidationException;

class AdminBootstrapService
{
    private static bool $grantingAdminRole = false;

    public function adminExists(): bool
    {
        return User::hasAdmin();
    }

    public static function isGrantingAdminRole(): bool
    {
        return static::$grantingAdminRole;
    }

    /**
     * @template TReturn
     *
     * @param  callable(): TReturn  $callback
     * @return TReturn
     */
    public static function withAdminGrant(callable $callback): mixed
    {
        static::$grantingAdminRole = true;

        try {
            return $callback();
        } finally {
            static::$grantingAdminRole = false;
        }
    }

    /**
     * @param  array{name?: string, store_name: string, currency_code: string, currency_symbol: string, currency_position: string, email: string, password: string}  $data
     */
    public function createAdmin(array $data): User
    {
        if ($this->adminExists()) {
            throw ValidationException::withMessages([
                'email' => ['An administrator account already exists.'],
            ]);
        }

        $name = $data['name'] ?? strstr($data['email'], '@', true) ?: 'Administrator';

        app(StoreSettingsService::class)->initializeAtSetup(
            $data['store_name'],
            $data['currency_code'],
            $data['currency_symbol'],
            CurrencyPosition::from($data['currency_position']),
        );

        $user = User::query()->create([
            'name' => $name,
            'email' => $data['email'],
            'password' => $data['password'],
            'is_active' => true,
        ]);

        static::withAdminGrant(function () use ($user): void {
            $user->forceFill([
                'role' => UserRole::Admin->value,
                'email_verified_at' => now(),
            ])->save();
        });

        return $user->refresh();
    }

    public static function passwordRules(): array
    {
        return PasswordRules::validationRules();
    }
}
