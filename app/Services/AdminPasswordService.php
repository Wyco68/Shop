<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\AdminPasswordChangeLog;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AdminPasswordService
{
    public function changePassword(string $email, string $plainPassword, string $channel = 'cli'): User
    {
        $admin = User::query()
            ->where('email', $email)
            ->where('role', UserRole::Admin->value)
            ->first();

        if (! $admin) {
            throw ValidationException::withMessages([
                'email' => ['No administrator found with that email.'],
            ]);
        }

        $admin->password = Hash::make($plainPassword);
        $admin->save();

        AdminPasswordChangeLog::query()->create([
            'user_id' => $admin->id,
            'email' => $admin->email,
            'ip_address' => request()->ip(),
            'channel' => $channel,
            'created_at' => now(),
        ]);

        return $admin;
    }
}
