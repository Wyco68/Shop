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

    public function ownerExists(): bool
    {
        return User::query()
            ->where('role', UserRole::Admin->value)
            ->where('is_owner', true)
            ->exists();
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

    /**
     * Create the single permanent owner admin. Distinct from the shared/demo
     * admin created by createAdmin(): the owner is exempt from demo-mode
     * restrictions (session cap, activity rollback, reset cooldown), and
     * password-reset links for the demo admin are always routed to the
     * owner's inbox. UserObserver::saving() rejects a second owner row.
     *
     * @param  array{name?: string, email: string, password: string}  $data
     */
    public function createOwner(array $data): User
    {
        if (! $this->adminExists()) {
            throw ValidationException::withMessages([
                'email' => ['Run store:setup to create the shared admin account first.'],
            ]);
        }

        if ($this->ownerExists()) {
            throw ValidationException::withMessages([
                'email' => ['An owner administrator account already exists.'],
            ]);
        }

        $name = $data['name'] ?? strstr($data['email'], '@', true) ?: 'Owner';

        $user = User::query()->create([
            'name' => $name,
            'email' => $data['email'],
            'password' => $data['password'],
            'is_active' => true,
        ]);

        static::withAdminGrant(function () use ($user): void {
            $user->forceFill([
                'role' => UserRole::Admin->value,
                'is_owner' => true,
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
