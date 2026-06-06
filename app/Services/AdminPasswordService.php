<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\AdminPasswordChangeLog;
use App\Models\User;
use App\Notifications\PasswordChangedNotification;
use App\Support\PasswordRules;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AdminPasswordService
{
    public function changePassword(string $email, string $plainPassword, string $channel = 'cli'): User
    {
        $admin = $this->findAdminByEmail($email);

        $this->validatePasswordStrength($plainPassword);

        $admin->forceFill([
            'password' => Hash::make($plainPassword),
            'remember_token' => Str::random(60),
        ])->save();

        $this->recordChange($admin, $channel);

        return $admin;
    }

    /**
     * Set the login (email + password) for the shared/demo admin account —
     * the one handed out publicly for testing. Never touches the owner
     * account. CLI-only, bypasses the web-change/cooldown restrictions that
     * exist specifically to stop testers from doing this themselves.
     */
    public function setDemoLoginCredentials(string $email, string $plainPassword, string $channel = 'cli'): User
    {
        $admin = $this->findDemoAdmin();

        $this->validatePasswordStrength($plainPassword);

        $email = strtolower(trim($email));

        $emailTaken = User::query()
            ->where('email', $email)
            ->where('id', '!=', $admin->id)
            ->exists();

        if ($emailTaken) {
            throw ValidationException::withMessages([
                'email' => ['That email is already in use by another account.'],
            ]);
        }

        $admin->forceFill([
            'email' => $email,
            'password' => Hash::make($plainPassword),
            'remember_token' => Str::random(60),
            'email_verified_at' => now(),
        ])->save();

        $this->recordChange($admin, $channel);

        return $admin;
    }

    private function findDemoAdmin(): User
    {
        $admin = User::query()
            ->where('role', UserRole::Admin->value)
            ->where('is_owner', false)
            ->first();

        if (! $admin) {
            throw ValidationException::withMessages([
                'email' => ['No shared/demo administrator exists yet. Run `php artisan store:setup` first.'],
            ]);
        }

        return $admin;
    }

    public function changePasswordForAuthenticatedAdmin(User $admin, string $currentPassword, string $newPassword): User
    {
        if (! $admin->isAdmin()) {
            abort(403);
        }

        if (! $admin->is_owner) {
            $this->assertWebChangeEnabled();
            $this->assertCooldownNotActive($admin);
        }

        if (! Hash::check($currentPassword, $admin->password)) {
            AuthSecurityLogger::log('admin_password_change_failed', [
                'user_id' => $admin->id,
                'email' => $admin->email,
                'reason' => 'invalid_current_password',
            ]);

            throw ValidationException::withMessages([
                'current_password' => 'The current password is incorrect.',
            ]);
        }

        if (Hash::check($newPassword, $admin->password)) {
            throw ValidationException::withMessages([
                'password' => 'The new password must be different from your current password.',
            ]);
        }

        $this->validatePasswordStrength($newPassword);

        Auth::logoutOtherDevices($currentPassword);

        $admin->forceFill([
            'password' => Hash::make($newPassword),
            'remember_token' => Str::random(60),
        ])->save();

        $this->recordChange($admin, 'web');

        $admin->notify(new PasswordChangedNotification);

        AuthSecurityLogger::log('admin_password_changed', [
            'user_id' => $admin->id,
            'email' => $admin->email,
            'channel' => 'web',
        ]);

        return $admin;
    }

    public function webChangeEnabled(): bool
    {
        return (bool) config('admin.password.web_change_enabled', true);
    }

    public function cooldownEndsAt(User $admin): ?\Illuminate\Support\Carbon
    {
        if ($admin->is_owner) {
            return null;
        }

        $cooldownMinutes = (int) config('admin.password.change_cooldown_minutes', 60);

        if ($cooldownMinutes <= 0) {
            return null;
        }

        $lastChange = AdminPasswordChangeLog::query()
            ->where('user_id', $admin->id)
            ->latest('created_at')
            ->first();

        if (! $lastChange) {
            return null;
        }

        $endsAt = $lastChange->created_at->copy()->addMinutes($cooldownMinutes);

        return $endsAt->isFuture() ? $endsAt : null;
    }

    public function passwordResetEnabled(): bool
    {
        return (bool) config('admin.password.reset_enabled', true);
    }

    public function passwordResetAllowed(User $user): bool
    {
        if (! $user->isAdmin()) {
            return true;
        }

        if ($user->is_owner) {
            return true;
        }

        if (! $this->passwordResetEnabled()) {
            return false;
        }

        return $this->cooldownEndsAt($user) === null;
    }

    public function recordPasswordReset(User $admin): void
    {
        if (! $admin->isAdmin()) {
            return;
        }

        $this->recordChange($admin, 'reset');

        AuthSecurityLogger::log('admin_password_reset_completed', [
            'user_id' => $admin->id,
            'email' => $admin->email,
        ]);
    }

    private function findAdminByEmail(string $email): User
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

        return $admin;
    }

    private function assertWebChangeEnabled(): void
    {
        if (! $this->webChangeEnabled()) {
            throw ValidationException::withMessages([
                'password' => 'Administrator password changes via the web panel are disabled. Use: php artisan admin:change-password',
            ]);
        }
    }

    private function assertCooldownNotActive(User $admin): void
    {
        $endsAt = $this->cooldownEndsAt($admin);

        if ($endsAt) {
            $minutes = now()->diffInMinutes($endsAt) + 1;

            throw ValidationException::withMessages([
                'password' => "For security, administrator passwords can only be changed once every ".config('admin.password.change_cooldown_minutes')." minutes. Try again in {$minutes} minute(s).",
            ]);
        }
    }

    private function validatePasswordStrength(string $plainPassword): void
    {
        $validator = Validator::make(
            ['password' => $plainPassword, 'password_confirmation' => $plainPassword],
            ['password' => PasswordRules::validationRules()],
        );

        if ($validator->fails()) {
            throw ValidationException::withMessages([
                'password' => $validator->errors()->first('password'),
            ]);
        }
    }

    private function recordChange(User $admin, string $channel): void
    {
        AdminPasswordChangeLog::query()->create([
            'user_id' => $admin->id,
            'email' => $admin->email,
            'ip_address' => request()->ip(),
            'channel' => $channel,
            'created_at' => now(),
        ]);
    }
}
