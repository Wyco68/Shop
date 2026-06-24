<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SetupRedirectTest extends TestCase
{
    use RefreshDatabase;

    public function test_visitors_get_unavailable_response_when_no_admin_exists(): void
    {
        $this->get('/')->assertStatus(503);
        $this->get('/login')->assertStatus(503);
        $this->get('/register')->assertStatus(503);
    }

    public function test_storefront_is_available_after_setup(): void
    {
        User::factory()->admin()->create();

        $this->get('/')->assertOk();
        $this->get('/login')->assertOk();
    }
}
