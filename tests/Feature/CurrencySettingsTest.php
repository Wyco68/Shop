<?php

namespace Tests\Feature;

use App\Models\StoreSetting;
use App\Models\User;
use App\Support\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class CurrencySettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        StoreSetting::query()->delete();
        Cache::flush();
    }

    public function test_currency_admin_routes_are_not_registered(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get('/admin/settings/currency')->assertNotFound();
        $this->actingAs($admin)->put('/admin/settings/currency')->assertNotFound();
    }

    public function test_setup_locks_currency(): void
    {
        $this->post('/setup', [
            'store_name' => 'New Shop',
            'currency_code' => 'THB',
            'currency_symbol' => '฿',
            'currency_position' => 'after',
            'email' => 'admin@shop.test',
            'password' => 'SecurePass1!Word',
            'password_confirmation' => 'SecurePass1!Word',
        ])->assertRedirect(route('login'));

        $this->assertDatabaseHas('store_settings', [
            'store_name' => 'New Shop',
            'currency_code' => 'THB',
            'currency_symbol' => '฿',
            'currency_position' => 'after',
            'currency_locked' => true,
        ]);

        app(\App\Services\StoreSettingsService::class)->refreshSettingsCache();

        $this->assertSame('1,234.56฿', Money::format(1234.56));
    }

    public function test_direct_model_update_blocked_when_locked(): void
    {
        $setting = StoreSetting::query()->create([
            'currency_code' => 'USD',
            'currency_symbol' => '$',
            'currency_position' => 'before',
            'currency_locked' => true,
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Currency cannot be changed after setup.');

        $setting->update(['currency_code' => 'EUR']);
    }

    public function test_cli_force_change_updates_locked_currency(): void
    {
        StoreSetting::query()->create([
            'currency_code' => 'USD',
            'currency_symbol' => '$',
            'currency_position' => 'before',
            'currency_locked' => true,
        ]);
        app(\App\Services\StoreSettingsService::class)->refreshSettingsCache();

        $this->artisan('currency:force-change', [
            '--code' => 'EUR',
            '--symbol' => '€',
            '--position' => 'before',
            '--force' => true,
        ])->assertSuccessful();

        $this->assertDatabaseHas('store_settings', [
            'currency_code' => 'EUR',
            'currency_symbol' => '€',
            'currency_locked' => true,
        ]);
    }
}
