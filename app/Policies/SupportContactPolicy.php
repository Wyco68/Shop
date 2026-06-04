<?php

namespace App\Policies;

use App\Models\SupportContact;
use App\Models\User;

class SupportContactPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, SupportContact $supportContact): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, SupportContact $supportContact): bool
    {
        return $user->isAdmin();
    }
}
