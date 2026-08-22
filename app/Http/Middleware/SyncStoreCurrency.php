<?php

namespace App\Http\Middleware;

use App\Services\StoreSettingsService;
use App\Support\SchemaCache;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SyncStoreCurrency
{
    public function handle(Request $request, Closure $next): Response
    {
        try {
            if (SchemaCache::hasTable('store_settings') && SchemaCache::hasColumn('store_settings', 'currency_code')) {
                app(StoreSettingsService::class)->syncCurrencyToConfig();
            }
        } catch (\Throwable) {
            //
        }

        return $next($request);
    }
}
