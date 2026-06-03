<?php

namespace App\Policies;

use App\Models\PaymentMethod;
use App\Models\User;

class PaymentMethodPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, PaymentMethod $paymentMethod): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, PaymentMethod $paymentMethod): bool
    {
        return $user->isAdmin();
    }
}
