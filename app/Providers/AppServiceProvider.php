<?php

namespace App\Providers;

use App\Events\OrderPlaced;
use App\Events\OrderStatusUpdated;
use App\Events\RefundApproved;
use App\Events\RefundRejected;
use App\Events\RefundRequested;
use App\Listeners\NotifyAdminOrderPlaced;
use App\Listeners\NotifyAdminRefundRequested;
use App\Listeners\NotifyUserOrderStatusUpdated;
use App\Listeners\NotifyUserRefundApproved;
use App\Listeners\NotifyUserRefundRejected;
use App\Services\StoreSettingsService;
use App\Services\SupportContactService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('auth', function (Request $request) {
            return Limit::perMinute(10)->by($request->ip());
        });

        RateLimiter::for('checkout', function (Request $request) {
            return Limit::perMinute(20)->by($request->user()?->id ?: $request->ip());
        });

        if ($this->app->environment('production')) {
            URL::forceScheme('https');

            if ($appUrl = config('app.url')) {
                URL::forceRootUrl($appUrl);
            }
        }

        // Render TLS is terminated at the edge; Secure cookies + undetected HTTPS = no Set-Cookie → 419
        if (env('RENDER')) {
            config([
                'session.secure' => false,
                'session.same_site' => 'lax',
            ]);
        }

        try {
            if (Schema::hasTable('store_settings')) {
                $storeSettings = app(StoreSettingsService::class);
                config(['shop.name' => $storeSettings->displayName()]);
                if (Schema::hasColumn('store_settings', 'currency_code')) {
                    $storeSettings->syncCurrencyToConfig();
                }
            }
        } catch (\Throwable) {
            // Database may be unavailable during console boot or package discovery.
        }

        View::composer(['layouts.app', 'layouts.admin', 'layouts.navigation', 'layouts.guest'], function ($view) {
            $settings = app(StoreSettingsService::class);
            $supportContacts = collect();

            try {
                if (Schema::hasTable('support_contacts')) {
                    $supportContacts = app(SupportContactService::class)->enabled();
                }
            } catch (\Throwable) {
                //
            }

            $view->with([
                'storeFaviconUrl' => $settings->faviconUrl(),
                'storeLogoUrl' => $settings->logoUrl(),
                'storeCurrencySymbol' => \App\Support\Money::symbol(),
                'supportContacts' => $supportContacts,
            ]);
        });
    }
}
