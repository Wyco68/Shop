<?php

namespace App\Services;

use App\Models\User;
use App\Notifications\PasswordChangedNotification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserPasswordService
{
    public function updatePassword(User $user, string $plainPassword, string $currentPassword): void
    {
        Auth::logoutOtherDevices($currentPassword);

        $user->forceFill([
            'password' => Hash::make($plainPassword),
            'remember_token' => Str::random(60),
        ])->save();

        $user->notify(new PasswordChangedNotification);
    }
}
