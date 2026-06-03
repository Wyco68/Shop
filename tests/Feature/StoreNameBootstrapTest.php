<?php

namespace Tests\Feature;

use App\Models\StoreSetting;
use App\Models\User;
use App\Services\AdminBootstrapService;
use App\Services\StoreSettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class StoreNameBootstrapTest extends TestCase
{
    use RefreshDatabase;

    public function test_store_name_is_set_once_at_bootstrap_only(): void
    {
        Cache::flush();

        $this->post('/setup', [
            'store_name' => 'Buyer Motors',
            'name' => 'Owner',
            'email' => 'owner@buyer.test',
            'password' => 'SecurePass1!Word',
            'password_confirmation' => 'SecurePass1!Word',
        ])->assertRedirect(route('login'));

        $this->assertSame('Buyer Motors', StoreSetting::query()->value('store_name'));

        $customer = User::factory()->create();
        $this->actingAs($customer)
            ->get(route('home'))
            ->assertOk()
            ->assertSee('Buyer Motors', false);

        StoreSetting::query()->delete();
        User::query()->delete();
        Cache::flush();

        app(AdminBootstrapService::class)->createAdmin([
            'store_name' => 'CLI Shop',
            'email' => 'cli@example.com',
            'password' => 'SecurePass1!Word',
        ]);

        $this->assertSame('CLI Shop', StoreSetting::query()->value('store_name'));

        $this->assertDatabaseHas('store_settings', ['store_name' => 'CLI Shop']);

        try {
            app(StoreSettingsService::class)->initializeStoreName('New Name');
            $this->fail('Expected RuntimeException when re-initializing store name.');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('already set', $e->getMessage());
        }
    }
}
