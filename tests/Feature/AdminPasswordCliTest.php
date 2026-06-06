<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\AdminPasswordChangeLog;
use App\Models\User;
use App\Services\AdminPasswordService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminPasswordCliTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_change_password_via_service(): void
    {
        $admin = User::factory()->admin()->create([
            'email' => 'admin@store.test',
            'password' => Hash::make('OldPassword1!Secure'),
        ]);

        app(AdminPasswordService::class)->changePassword('admin@store.test', 'NewPassword2!Secure', 'cli');

        $admin->refresh();
        $this->assertTrue(Hash::check('NewPassword2!Secure', $admin->password));
        $this->assertDatabaseHas('admin_password_change_logs', [
            'email' => 'admin@store.test',
            'channel' => 'cli',
        ]);
    }

    public function test_admin_cannot_update_password_via_profile(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->patch('/profile', [
            'name' => $admin->name,
            'email' => $admin->email,
            'password' => 'NewPassword1!Secure',
            'password_confirmation' => 'NewPassword1!Secure',
        ]);

        $response->assertRedirect(route('admin.dashboard'));
    }

    public function test_branding_settings_requires_admin(): void
    {
        User::factory()->admin()->create();
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('admin.settings.branding.edit'))->assertForbidden();
    }

    public function test_branding_settings_accessible_by_admin(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get(route('admin.settings.branding.edit'))->assertOk();
    }
}
