<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SetupRedirectTest extends TestCase
{
    use RefreshDatabase;

    public function test_visitors_are_redirected_to_setup_when_no_admin_exists(): void
    {
        $this->get('/')->assertRedirect(route('setup.create'));
        $this->get('/login')->assertRedirect(route('setup.create'));
        $this->get('/register')->assertRedirect(route('setup.create'));
    }

    public function test_setup_page_is_available_when_no_admin_exists(): void
    {
        $this->get('/setup')->assertOk();
    }

    public function test_storefront_is_available_after_setup(): void
    {
        User::factory()->admin()->create();

        $this->get('/')->assertOk();
        $this->get('/login')->assertOk();
    }
}
