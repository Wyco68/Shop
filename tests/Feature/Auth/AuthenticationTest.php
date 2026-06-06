<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BootstrapsStore;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use BootstrapsStore;
    use RefreshDatabase;

    private const PASSWORD = 'Password1!Secure';

    protected function setUp(): void
    {
        parent::setUp();

        $this->bootstrapStore();
    }

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $user = User::factory()->create();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => self::PASSWORD,
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('home', absolute: false));
    }

    public function test_unverified_users_cannot_login(): void
    {
        $user = User::factory()->unverified()->create();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => self::PASSWORD,
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('email', null, 'unverified');
    }

    public function test_admins_are_redirected_to_admin_dashboard_after_login(): void
    {
        $admin = User::query()->where('role', 'admin')->firstOrFail();

        $response = $this->post('/login', [
            'email' => $admin->email,
            'password' => self::PASSWORD,
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('admin.dashboard', absolute: false));
    }

    public function test_authenticated_admin_visiting_login_is_redirected_to_admin_dashboard(): void
    {
        $admin = User::query()->where('role', 'admin')->firstOrFail();

        $this->actingAs($admin)
            ->get('/login')
            ->assertRedirect(route('admin.dashboard', absolute: false));
    }

    public function test_inactive_users_cannot_login(): void
    {
        $user = User::factory()->create(['is_active' => false]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => self::PASSWORD,
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('email');
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/login');
    }
}
