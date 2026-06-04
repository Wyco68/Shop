<?php

namespace Tests\Unit;

use App\Enums\CurrencyPosition;
use App\Models\StoreSetting;
use App\Services\StoreSettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class FormatCurrencyHelperTest extends TestCase
{
    use RefreshDatabase;

    public function test_format_currency_helper_uses_database_settings(): void
    {
        Cache::flush();

        $setting = StoreSetting::query()->create([
            'currency_code' => 'EUR',
            'currency_symbol' => '€',
            'currency_position' => CurrencyPosition::Before->value,
        ]);

        app(StoreSettingsService::class)->syncCurrencyToConfig($setting);

        $this->assertSame('€99.00', format_currency(99));
    }
}
