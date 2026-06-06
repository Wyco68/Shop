<?php

namespace App\Models;

use App\Enums\UserRole;
use App\Notifications\ResetPasswordNotification;
use App\Notifications\VerifyEmailNotification;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name', 'email', 'password', 'phone_num', 'address', 'is_active', 'notify_order_status_email',
    ];

    protected $hidden = [
        'password', 'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'notify_order_status_email' => 'boolean',
            'is_owner' => 'boolean',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin->value;
    }

    /**
     * The single permanent admin account, exempt from demo-mode restrictions
     * (session cap, activity rollback, password-reset cooldown).
     */
    public function isOwnerAdmin(): bool
    {
        return $this->isAdmin() && (bool) $this->is_owner;
    }

    /**
     * Default post-authentication URL for this user (storefront vs admin).
     */
    public function homeUrl(): string
    {
        return $this->isAdmin()
            ? route('admin.dashboard', absolute: false)
            : route('home', absolute: false);
    }

    public static function hasAdmin(): bool
    {
        return static::query()->where('role', UserRole::Admin->value)->exists();
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function cart()
    {
        return $this->hasOne(Cart::class);
    }

    public function spending()
    {
        return $this->hasOne(UserSpending::class);
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class)->orderByDesc('created_at');
    }

    public function refundRequests()
    {
        return $this->hasMany(RefundRequest::class);
    }

    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new VerifyEmailNotification);
    }

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetPasswordNotification($token));
    }

    /**
     * Shared/demo admin credentials are public. Route all mail for that account
     * (password resets included) to the real owner's inbox so only the owner can
     * ever act on it, regardless of what email is on file for the demo account.
     */
    public function routeNotificationForMail($notification): string
    {
        if ($this->isAdmin() && ! $this->is_owner) {
            $ownerEmail = config('admin.owner_email');

            if ($ownerEmail) {
                return $ownerEmail;
            }
        }

        return $this->email;
    }
}
