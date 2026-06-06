<?php

namespace App\Observers;

use App\Enums\UserRole;
use App\Models\User;
use App\Services\AdminBootstrapService;
use Illuminate\Validation\ValidationException;

class UserObserver
{
    public function creating(User $user): void
    {
        if (! AdminBootstrapService::isGrantingAdminRole()) {
            $user->role = UserRole::User->value;
        }
    }

    public function saving(User $user): void
    {
        if (! AdminBootstrapService::isGrantingAdminRole() && $user->isDirty('is_owner')) {
            $user->setAttribute(
                'is_owner',
                $user->exists ? (bool) $user->getOriginal('is_owner') : false,
            );
        }

        if (! $user->isDirty('role')) {
            return;
        }

        if (! AdminBootstrapService::isGrantingAdminRole()) {
            $user->setAttribute(
                'role',
                $user->exists
                    ? ($user->getOriginal('role') ?? UserRole::User->value)
                    : UserRole::User->value,
            );

            return;
        }

        if ($user->role !== UserRole::Admin->value) {
            return;
        }

        // At most two admin rows may ever exist: one shared/demo admin (is_owner = false)
        // and one designated owner (is_owner = true). This mirrors the original
        // single-admin guard while allowing exactly one permanent owner account.
        if ($user->is_owner && $this->anotherAdminExists($user, onlyOwners: true)) {
            throw ValidationException::withMessages([
                'email' => ['An owner administrator account already exists.'],
            ]);
        }

        if (! $user->is_owner && $this->anotherAdminExists($user, onlyOwners: false)) {
            throw ValidationException::withMessages([
                'email' => ['An administrator account already exists.'],
            ]);
        }
    }

    private function anotherAdminExists(User $user, bool $onlyOwners): bool
    {
        return User::query()
            ->where('role', UserRole::Admin->value)
            ->where('is_owner', $onlyOwners)
            ->when($user->exists, fn ($query) => $query->where('id', '!=', $user->id))
            ->exists();
    }
}
