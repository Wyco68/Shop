<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use App\Services\AdminBootstrapService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class AdminOwnerTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_be_created_once_shared_admin_exists(): void
    {
        User::factory()->admin()->create();

        $owner = app(AdminBootstrapService::class)->createOwner([
            'email' => 'herik.dev06@gmail.com',
            'password' => 'OwnerPassword1!Secure',
        ]);

        $this->assertTrue($owner->isAdmin());
        $this->assertTrue($owner->isOwnerAdmin());
        $this->assertTrue(Hash::check('OwnerPassword1!Secure', $owner->password));
    }

    public function test_owner_creation_requires_shared_admin_to_exist_first(): void
    {
        $this->expectException(ValidationException::class);

        app(AdminBootstrapService::class)->createOwner([
            'email' => 'herik.dev06@gmail.com',
            'password' => 'OwnerPassword1!Secure',
        ]);
    }

    public function test_a_second_owner_cannot_be_created(): void
    {
        User::factory()->admin()->create();
        app(AdminBootstrapService::class)->createOwner([
            'email' => 'herik.dev06@gmail.com',
            'password' => 'OwnerPassword1!Secure',
        ]);

        $this->expectException(ValidationException::class);

        app(AdminBootstrapService::class)->createOwner([
            'email' => 'someone-else@example.com',
            'password' => 'OwnerPassword1!Secure',
        ]);
    }

    public function test_a_third_admin_row_still_cannot_be_created_directly(): void
    {
        User::factory()->admin()->create();
        app(AdminBootstrapService::class)->createOwner([
            'email' => 'herik.dev06@gmail.com',
            'password' => 'OwnerPassword1!Secure',
        ]);

        $this->expectException(ValidationException::class);

        AdminBootstrapService::withAdminGrant(function () {
            User::factory()->create()->forceFill(['role' => UserRole::Admin->value])->save();
        });
    }

    public function test_owner_is_exempt_from_web_change_disabled_and_cooldown(): void
    {
        config([
            'admin.password.web_change_enabled' => false,
            'admin.password.change_cooldown_minutes' => 60,
        ]);

        User::factory()->admin()->create();
        $owner = app(AdminBootstrapService::class)->createOwner([
            'email' => 'herik.dev06@gmail.com',
            'password' => 'OwnerPassword1!Secure',
        ]);

        $response = $this->actingAs($owner)->put(route('admin.settings.security.password.update'), [
            'current_password' => 'OwnerPassword1!Secure',
            'password' => 'NewOwnerPassword2!Secure',
            'password_confirmation' => 'NewOwnerPassword2!Secure',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertTrue(Hash::check('NewOwnerPassword2!Secure', $owner->refresh()->password));
    }

    public function test_shared_admin_is_still_blocked_by_web_change_disabled(): void
    {
        config(['admin.password.web_change_enabled' => false]);

        $admin = User::factory()->admin()->create([
            'password' => Hash::make('OldPassword1!Secure'),
        ]);

        $this->actingAs($admin)->put(route('admin.settings.security.password.update'), [
            'current_password' => 'OldPassword1!Secure',
            'password' => 'NewPassword2!Secure',
            'password_confirmation' => 'NewPassword2!Secure',
        ])->assertSessionHasErrors('password');

        $this->assertTrue(Hash::check('OldPassword1!Secure', $admin->refresh()->password));
    }
}
