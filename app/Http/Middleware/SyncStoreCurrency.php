<?php

namespace App\Http\Middleware;

use App\Services\StoreSettingsService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

class SyncStoreCurrency
{
    public function handle(Request $request, Closure $next): Response
    {
        try {
            if (Schema::hasTable('store_settings') && Schema::hasColumn('store_settings', 'currency_code')) {
                app(StoreSettingsService::class)->syncCurrencyToConfig();
            }
        } catch (\Throwable) {
            //
        }

        return $next($request);
    }
}
