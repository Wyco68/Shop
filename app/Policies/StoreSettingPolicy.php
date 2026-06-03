<?php

namespace App\Policies;

use App\Models\StoreSetting;
use App\Models\User;

class StoreSettingPolicy
{
    public function update(User $user, ?StoreSetting $storeSetting = null): bool
    {
        return $user->isAdmin();
    }
}
