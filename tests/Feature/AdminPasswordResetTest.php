<?php

namespace Tests\Feature;

use App\Models\AdminPasswordChangeLog;
use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class AdminPasswordResetTest extends TestCase
{
    use RefreshDatabase;

    private const OLD_PASSWORD = 'OldPassword1!Secure';
    private const NEW_PASSWORD = 'NewPassword2!Secure';

    public function test_admin_can_request_and_complete_password_reset(): void
    {
        Notification::fake();

        $admin = User::factory()->admin()->create([
            'email' => 'admin@store.test',
            'password' => Hash::make(self::OLD_PASSWORD),
        ]);

        $this->post('/forgot-password', ['email' => $admin->email])
            ->assertSessionHas('status');

        Notification::assertSentTo($admin, ResetPasswordNotification::class);

        Notification::assertSentTo($admin, ResetPasswordNotification::class, function ($notification) use ($admin) {
            $response = $this->post('/reset-password', [
                'token' => $notification->token,
                'email' => $admin->email,
                'password' => self::NEW_PASSWORD,
                'password_confirmation' => self::NEW_PASSWORD,
            ]);

            $response
                ->assertSessionHasNoErrors()
                ->assertRedirect(route('login'));

            return true;
        });

        $this->assertTrue(Hash::check(self::NEW_PASSWORD, $admin->refresh()->password));
        $this->assertDatabaseHas('admin_password_change_logs', [
            'user_id' => $admin->id,
            'channel' => 'reset',
        ]);
    }

    public function test_admin_reset_link_not_sent_during_cooldown_after_manual_change(): void
    {
        Notification::fake();

        $admin = User::factory()->admin()->create([
            'email' => 'admin@store.test',
            'password' => Hash::make(self::OLD_PASSWORD),
        ]);

        AdminPasswordChangeLog::query()->create([
            'user_id' => $admin->id,
            'email' => $admin->email,
            'ip_address' => '127.0.0.1',
            'channel' => 'web',
            'created_at' => now(),
        ]);

        config(['admin.password.change_cooldown_minutes' => 60]);

        $this->post('/forgot-password', ['email' => $admin->email])
            ->assertSessionHas('status', __(Password::RESET_LINK_SENT));

        Notification::assertNothingSent();
    }

    public function test_admin_cannot_complete_reset_during_cooldown_even_with_token(): void
    {
        Notification::fake();

        $admin = User::factory()->admin()->create([
            'email' => 'admin@store.test',
            'password' => Hash::make(self::OLD_PASSWORD),
        ]);

        $this->post('/forgot-password', ['email' => $admin->email]);

        $token = null;
        Notification::assertSentTo($admin, ResetPasswordNotification::class, function ($notification) use (&$token) {
            $token = $notification->token;

            return true;
        });

        AdminPasswordChangeLog::query()->create([
            'user_id' => $admin->id,
            'email' => $admin->email,
            'ip_address' => '127.0.0.1',
            'channel' => 'web',
            'created_at' => now(),
        ]);

        config(['admin.password.change_cooldown_minutes' => 60]);

        $this->post('/reset-password', [
            'token' => $token,
            'email' => $admin->email,
            'password' => self::NEW_PASSWORD,
            'password_confirmation' => self::NEW_PASSWORD,
        ])->assertSessionHasErrors('email');

        $this->assertTrue(Hash::check(self::OLD_PASSWORD, $admin->refresh()->password));
    }

    public function test_admin_reset_disabled_via_env_shows_generic_success_without_email(): void
    {
        Notification::fake();

        config(['admin.password.reset_enabled' => false]);

        $admin = User::factory()->admin()->create([
            'email' => 'admin@store.test',
        ]);

        $this->post('/forgot-password', ['email' => $admin->email])
            ->assertSessionHas('status', __(Password::RESET_LINK_SENT));

        Notification::assertNothingSent();
    }

    public function test_regular_user_reset_unaffected_by_admin_cooldown_rules(): void
    {
        Notification::fake();

        User::factory()->admin()->create();
        $user = User::factory()->create(['email' => 'customer@store.test']);

        $this->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPasswordNotification::class);
    }

    public function test_admin_reset_allowed_after_cooldown_expires(): void
    {
        Notification::fake();

        $admin = User::factory()->admin()->create([
            'email' => 'admin@store.test',
        ]);

        AdminPasswordChangeLog::query()->create([
            'user_id' => $admin->id,
            'email' => $admin->email,
            'ip_address' => '127.0.0.1',
            'channel' => 'web',
            'created_at' => now()->subMinutes(61),
        ]);

        config(['admin.password.change_cooldown_minutes' => 60]);

        $this->post('/forgot-password', ['email' => $admin->email]);

        Notification::assertSentTo($admin, ResetPasswordNotification::class);
    }

    public function test_security_page_shows_reset_disabled_during_cooldown(): void
    {
        $admin = User::factory()->admin()->create();

        AdminPasswordChangeLog::query()->create([
            'user_id' => $admin->id,
            'email' => $admin->email,
            'ip_address' => '127.0.0.1',
            'channel' => 'web',
            'created_at' => now(),
        ]);

        config(['admin.password.change_cooldown_minutes' => 60]);

        $this->actingAs($admin)
            ->get(route('admin.settings.security.edit'))
            ->assertOk()
            ->assertSee('Email reset is temporarily disabled');
    }
}
