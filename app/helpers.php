<?php

use App\Models\StoreSetting;
use App\Services\StoreSettingsService;

if (! function_exists('get_settings')) {
    function get_settings(): StoreSetting
    {
        return app(StoreSettingsService::class)->get();
    }
}

if (! function_exists('format_currency')) {
    function format_currency(float|int|string|null $amount, int $decimals = 2): string
    {
        return \App\Support\Money::format($amount, $decimals);
    }
}
