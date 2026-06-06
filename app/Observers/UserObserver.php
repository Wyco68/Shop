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

        if ($user->role === UserRole::Admin->value && $this->anotherAdminExists($user)) {
            throw ValidationException::withMessages([
                'email' => ['An administrator account already exists.'],
            ]);
        }
    }

    private function anotherAdminExists(User $user): bool
    {
        return User::query()
            ->where('role', UserRole::Admin->value)
            ->when($user->exists, fn ($query) => $query->where('id', '!=', $user->id))
            ->exists();
    }
}
