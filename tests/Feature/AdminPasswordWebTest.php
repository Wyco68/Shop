<?php

namespace Tests\Feature;

use App\Models\AdminPasswordChangeLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminPasswordWebTest extends TestCase
{
    use RefreshDatabase;

    private const OLD_PASSWORD = 'OldPassword1!Secure';
    private const NEW_PASSWORD = 'NewPassword2!Secure';

    public function test_security_settings_page_is_directly_accessible(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('admin.settings.security.edit'))
            ->assertOk()
            ->assertSee('Account security')
            ->assertSee('Update administrator password');
    }

    public function test_admin_can_change_password_via_security_settings(): void
    {
        $admin = User::factory()->admin()->create([
            'password' => Hash::make(self::OLD_PASSWORD),
        ]);

        $response = $this->actingAs($admin)
            ->put(route('admin.settings.security.password.update'), [
                'current_password' => self::OLD_PASSWORD,
                'password' => self::NEW_PASSWORD,
                'password_confirmation' => self::NEW_PASSWORD,
            ]);

        $response
            ->assertRedirect(route('admin.settings.security.edit'))
            ->assertSessionHas('status', 'admin-password-updated');

        $this->assertTrue(Hash::check(self::NEW_PASSWORD, $admin->refresh()->password));
        $this->assertDatabaseHas('admin_password_change_logs', [
            'user_id' => $admin->id,
            'channel' => 'web',
        ]);
    }

    public function test_admin_password_change_requires_correct_current_password(): void
    {
        $admin = User::factory()->admin()->create([
            'password' => Hash::make(self::OLD_PASSWORD),
        ]);

        $this->actingAs($admin)
            ->put(route('admin.settings.security.password.update'), [
                'current_password' => 'WrongPassword1!',
                'password' => self::NEW_PASSWORD,
                'password_confirmation' => self::NEW_PASSWORD,
            ])
            ->assertSessionHasErrors('current_password');
    }

    public function test_admin_password_change_rejects_reused_password(): void
    {
        $admin = User::factory()->admin()->create([
            'password' => Hash::make(self::OLD_PASSWORD),
        ]);

        $this->actingAs($admin)
            ->put(route('admin.settings.security.password.update'), [
                'current_password' => self::OLD_PASSWORD,
                'password' => self::OLD_PASSWORD,
                'password_confirmation' => self::OLD_PASSWORD,
            ])
            ->assertSessionHasErrors('password');
    }

    public function test_admin_cannot_change_password_via_profile(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->patch('/profile', [
                'name' => $admin->name,
                'email' => $admin->email,
                'password' => self::NEW_PASSWORD,
                'password_confirmation' => self::NEW_PASSWORD,
            ])
            ->assertRedirect(route('admin.dashboard'));
    }

    public function test_non_admin_cannot_access_security_settings(): void
    {
        User::factory()->admin()->create();
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('admin.settings.security.edit'))
            ->assertForbidden();
    }
}
