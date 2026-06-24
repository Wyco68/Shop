<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SetupTest extends TestCase
{
    use RefreshDatabase;

    public function test_setup_route_no_longer_exists(): void
    {
        $this->get('/setup')->assertNotFound();
        $this->post('/setup')->assertNotFound();
    }

    public function test_setup_command_refuses_when_admin_exists(): void
    {
        User::factory()->admin()->create();

        $this->artisan('store:setup')->assertFailed();
    }

    public function test_setup_command_creates_admin(): void
    {
        $this->artisan('store:setup')
            ->expectsQuestion('Store name', 'My Store')
            ->expectsQuestion('Currency code (3-letter, e.g. USD)', 'USD')
            ->expectsQuestion('Currency symbol (e.g. $)', '$')
            ->expectsChoice('Currency symbol position', 'before', ['before', 'after'])
            ->expectsQuestion('Administrator name (optional, defaults to part before @ in email)', 'Admin')
            ->expectsQuestion('Administrator email', 'admin@store.test')
            ->expectsQuestion('Administrator password', 'SecurePass1!Word')
            ->expectsQuestion('Confirm administrator password', 'SecurePass1!Word')
            ->assertSuccessful();

        $this->assertTrue(User::where('email', 'admin@store.test')->first()?->isAdmin() ?? false);
    }
}
