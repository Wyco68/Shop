<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SetupTest extends TestCase
{
    use RefreshDatabase;

    public function test_setup_available_when_no_admin(): void
    {
        $this->get('/setup')->assertOk();
    }

    public function test_setup_hidden_after_admin_exists(): void
    {
        User::factory()->admin()->create();

        $this->get('/setup')->assertNotFound();
        $this->post('/setup', [
            'email' => 'new@example.com',
            'password' => 'SecurePass1!Word',
            'password_confirmation' => 'SecurePass1!Word',
        ])->assertNotFound();
    }

    public function test_setup_creates_admin(): void
    {
        $response = $this->post('/setup', [
            'name' => 'Admin',
            'email' => 'admin@store.test',
            'password' => 'SecurePass1!Word',
            'password_confirmation' => 'SecurePass1!Word',
        ]);

        $response->assertRedirect(route('login'));
        $this->assertTrue(User::where('email', 'admin@store.test')->first()?->isAdmin() ?? false);
    }
}
