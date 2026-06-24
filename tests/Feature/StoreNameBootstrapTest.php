<?php

namespace Tests\Feature;

use App\Enums\CurrencyPosition;
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

    public function test_store_name_and_currency_set_once_at_bootstrap_only(): void
    {
        Cache::flush();

        app(AdminBootstrapService::class)->createAdmin([
            'store_name' => 'Buyer Motors',
            'currency_code' => 'USD',
            'currency_symbol' => '$',
            'currency_position' => 'before',
            'name' => 'Owner',
            'email' => 'owner@buyer.test',
            'password' => 'SecurePass1!Word',
        ]);

        $this->assertSame('Buyer Motors', StoreSetting::query()->value('store_name'));
        $this->assertTrue((bool) StoreSetting::query()->value('currency_locked'));

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
            'currency_code' => 'USD',
            'currency_symbol' => '$',
            'currency_position' => 'before',
            'email' => 'cli@example.com',
            'password' => 'SecurePass1!Word',
        ]);

        $this->assertSame('CLI Shop', StoreSetting::query()->value('store_name'));

        $this->expectException(\RuntimeException::class);

        app(StoreSettingsService::class)->initializeAtSetup(
            'New Name',
            'EUR',
            '€',
            CurrencyPosition::Before,
        );
    }
}
