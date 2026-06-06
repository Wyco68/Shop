<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use App\Services\AdminBootstrapService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class InitAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_first_admin_via_service(): void
    {
        $admin = app(AdminBootstrapService::class)->createAdmin([
            'store_name' => 'Store Owner Shop',
            'currency_code' => 'USD',
            'currency_symbol' => '$',
            'currency_position' => 'before',
            'name' => 'Store Owner',
            'email' => 'owner@example.com',
            'password' => 'SecurePass1!Word',
        ]);

        $this->assertTrue($admin->isAdmin());
        $this->assertDatabaseHas('users', [
            'email' => 'owner@example.com',
            'role' => UserRole::Admin->value,
        ]);
    }

    public function test_rejects_second_admin_via_service(): void
    {
        User::factory()->admin()->create();

        $this->expectException(ValidationException::class);

        app(AdminBootstrapService::class)->createAdmin([
            'store_name' => 'Other Shop',
            'currency_code' => 'USD',
            'currency_symbol' => '$',
            'currency_position' => 'before',
            'email' => 'other@example.com',
            'password' => 'SecurePass1!Word',
        ]);
    }

    public function test_registration_cannot_set_admin_role(): void
    {
        User::factory()->admin()->create();

        $response = $this->post('/register', [
            'name' => 'Hacker',
            'email' => 'hack@example.com',
            'password' => 'Password1!Secure',
            'password_confirmation' => 'Password1!Secure',
            'phone_num' => '1234567890',
            'address' => '123 Main St',
            'role' => UserRole::Admin->value,
        ]);

        $response->assertSessionHasErrors('role');

        $this->assertDatabaseMissing('users', [
            'email' => 'hack@example.com',
        ]);
    }
}
