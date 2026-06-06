<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use App\Services\AdminBootstrapService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class AdminAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private const PASSWORD = 'Password1!Secure';

    public function test_setup_creates_the_only_admin_account(): void
    {
        $this->post('/setup', [
            'store_name' => 'Secure Shop',
            'currency_code' => 'USD',
            'currency_symbol' => '$',
            'currency_position' => 'before',
            'name' => 'Owner',
            'email' => 'owner@store.test',
            'password' => self::PASSWORD,
            'password_confirmation' => self::PASSWORD,
        ])->assertRedirect(route('login'));

        $this->assertEquals(1, User::query()->where('role', UserRole::Admin->value)->count());
    }

    public function test_setup_cannot_be_repeated_after_admin_exists(): void
    {
        User::factory()->admin()->create();

        $this->get('/setup')->assertNotFound();
        $this->post('/setup', [
            'store_name' => 'Another Shop',
            'currency_code' => 'USD',
            'currency_symbol' => '$',
            'currency_position' => 'before',
            'email' => 'second@store.test',
            'password' => self::PASSWORD,
            'password_confirmation' => self::PASSWORD,
        ])->assertNotFound();

        $this->assertEquals(1, User::query()->where('role', UserRole::Admin->value)->count());
    }

    public function test_registration_rejects_role_field_and_creates_user_role(): void
    {
        User::factory()->admin()->create();

        $this->post('/register', [
            'name' => 'Attacker',
            'email' => 'attacker@store.test',
            'password' => self::PASSWORD,
            'password_confirmation' => self::PASSWORD,
            'phone_num' => '555-0001',
            'address' => '123 Test St',
            'role' => UserRole::Admin->value,
        ])->assertSessionHasErrors('role');

        $this->assertDatabaseMissing('users', ['email' => 'attacker@store.test']);
    }

    public function test_registration_cannot_promote_to_admin_even_without_validation_error_path(): void
    {
        User::factory()->admin()->create();

        $this->post('/register', [
            'name' => 'Regular User',
            'email' => 'user@store.test',
            'password' => self::PASSWORD,
            'password_confirmation' => self::PASSWORD,
            'phone_num' => '555-0001',
            'address' => '123 Test St',
        ]);

        $this->assertDatabaseHas('users', [
            'email' => 'user@store.test',
            'role' => UserRole::User->value,
        ]);
    }

    public function test_profile_update_rejects_role_escalation(): void
    {
        User::factory()->admin()->create();
        $user = User::factory()->create();

        $this->actingAs($user)
            ->patch('/profile', [
                'name' => $user->name,
                'email' => $user->email,
                'role' => UserRole::Admin->value,
            ])
            ->assertSessionHasErrors('role');

        $this->assertFalse($user->fresh()->isAdmin());
    }

    public function test_model_observer_blocks_direct_role_escalation(): void
    {
        User::factory()->admin()->create();
        $user = User::factory()->create();

        $user->role = UserRole::Admin->value;
        $user->save();

        $this->assertFalse($user->fresh()->isAdmin());
    }

    public function test_factory_cannot_create_second_admin(): void
    {
        User::factory()->admin()->create();

        $this->expectException(ValidationException::class);

        User::factory()->admin()->create();
    }

    public function test_service_rejects_second_admin_creation(): void
    {
        User::factory()->admin()->create();

        $this->expectException(ValidationException::class);

        app(AdminBootstrapService::class)->createAdmin([
            'store_name' => 'Other Shop',
            'currency_code' => 'USD',
            'currency_symbol' => '$',
            'currency_position' => 'before',
            'email' => 'second-admin@store.test',
            'password' => self::PASSWORD,
        ]);
    }

    public function test_regular_user_cannot_access_admin_endpoints(): void
    {
        User::factory()->admin()->create();
        $user = User::factory()->create();

        $adminRoutes = [
            ['GET', '/admin'],
            ['GET', '/admin/products'],
            ['GET', '/admin/orders'],
            ['GET', '/admin/users'],
            ['GET', '/admin/settings/security'],
            ['GET', '/admin/settings/branding'],
            ['GET', '/admin/analytics'],
        ];

        foreach ($adminRoutes as [$method, $uri]) {
            $this->actingAs($user)->call($method, $uri)->assertForbidden();
        }
    }

    public function test_guest_cannot_access_admin_endpoints(): void
    {
        User::factory()->admin()->create();

        $this->get('/admin')->assertRedirect(route('login'));
        $this->get('/admin/products')->assertRedirect(route('login'));
    }

    public function test_admin_can_access_admin_dashboard(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get('/admin')
            ->assertOk();
    }
}
