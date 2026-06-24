<?php

namespace App\Providers;

use App\Models\User;
use App\Observers\UserObserver;
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
use App\Support\PasswordRules;
use Illuminate\Support\Facades\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

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
        User::observe(UserObserver::class);

        Password::defaults(fn () => PasswordRules::defaults());

        RateLimiter::for('auth', function (Request $request) {
            return Limit::perMinute(10)->by($request->ip());
        });

        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(5)->by(
                strtolower((string) $request->input('email')).'|'.$request->ip()
            );
        });

        RateLimiter::for('password-reset', function (Request $request) {
            return Limit::perMinute(3)->by($request->ip());
        });

        RateLimiter::for('email-resend', function (Request $request) {
            return Limit::perMinute(3)->by(
                strtolower((string) $request->input('email')).'|'.$request->ip()
            );
        });

        RateLimiter::for('admin-password', function (Request $request) {
            $window = max(1, (int) config('admin.password.rate_limit_minutes', 15));

            return Limit::perMinutes($window, (int) config('admin.password.max_attempts', 3))
                ->by('admin-password|'.($request->user()?->id ?: $request->ip()));
        });

        RateLimiter::for('checkout', function (Request $request) {
            return Limit::perMinute(20)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('confirm-password', function (Request $request) {
            return Limit::perMinute(30)->by(
                'confirm-password|'.($request->user()?->id ?: $request->ip())
            );
        });

        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->ip());
        });

        if ($this->app->environment('production')) {
            $appUrl = config('app.url');

            // Only force https when APP_URL actually is https — a VPS with no domain yet
            // (bare IP, plain HTTP) would otherwise get its own links forced to a scheme
            // nothing is listening on, breaking every generated URL.
            if ($appUrl && str_starts_with($appUrl, 'https://')) {
                URL::forceScheme('https');
            }

            if ($appUrl) {
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
