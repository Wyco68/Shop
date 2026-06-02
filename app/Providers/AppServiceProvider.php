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
use Illuminate\Support\Facades\Event;
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
        if ($this->app->environment('production')) {
            URL::forceScheme('https');

            if ($appUrl = config('app.url')) {
                URL::forceRootUrl($appUrl);
            }
        }

        // Render TLS is terminated at the edge; Secure cookies + undetected HTTPS = no Set-Cookie → 419
        if (config('app.demo_mode') || env('RENDER')) {
            config([
                'session.secure' => false,
                'session.same_site' => 'lax',
            ]);
        }
    }
}
