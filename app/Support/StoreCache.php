<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;

/**
 * Two invalidation styles are in use here, by design: caches with one clear
 * owning write-path (settings, categories, support contacts, payment methods)
 * get an explicit forget*() called from that write path. Caches over data
 * mutated from many unrelated places (dashboard/analytics aggregates) rely on
 * a short TTL instead — wiring forget() into every mutating controller would
 * be more fragile than tolerating a few seconds of staleness on admin-only reads.
 */
class StoreCache
{
    public const CATEGORIES_ACTIVE = 'categories.active';

    public const CATEGORIES_INDEX = 'categories.index';

    public const HOME_FEATURED = 'home.featured';

    public const SETTINGS = 'cache:settings';

    public const SUPPORT_CONTACTS = 'cache:support_contacts';

    public const PRODUCT_LISTINGS_REGISTRY = 'products.index.registry';

    public const DASHBOARD_STATS = 'cache:admin_dashboard_stats';

    public const DASHBOARD_SETUP_HINTS = 'cache:admin_dashboard_setup_hints';

    public const ANALYTICS_DASHBOARD_STATS = 'cache:analytics_dashboard_stats';

    public const ANALYTICS_MONTHLY_EARNINGS = 'cache:analytics_monthly_earnings';

    public const ANALYTICS_YEARLY_EARNINGS = 'cache:analytics_yearly_earnings';

    public const ANALYTICS_USER_SPENDING = 'cache:analytics_user_spending';

    public const ACTIVE_PAYMENT_METHODS = 'cache:active_payment_methods';

    public static function forgetSettings(): void
    {
        Cache::forget(self::SETTINGS);
    }

    public static function forgetSupportContacts(): void
    {
        Cache::forget(self::SUPPORT_CONTACTS);
    }

    public static function forgetPaymentMethods(): void
    {
        Cache::forget(self::ACTIVE_PAYMENT_METHODS);
    }

    public static function forgetCategories(): void
    {
        Cache::forget(self::CATEGORIES_ACTIVE);
        Cache::forget(self::CATEGORIES_INDEX);
    }

    public static function forgetProducts(): void
    {
        Cache::forget(self::HOME_FEATURED);

        $keys = Cache::get(self::PRODUCT_LISTINGS_REGISTRY, []);
        foreach ($keys as $key) {
            Cache::forget($key);
        }
        Cache::forget(self::PRODUCT_LISTINGS_REGISTRY);
    }

    public static function productListingKey(array $filters, int $page = 1): string
    {
        ksort($filters);

        return 'products.index.'.md5(json_encode($filters).'.p'.$page);
    }

    public static function registerProductListingKey(string $key): void
    {
        $keys = Cache::get(self::PRODUCT_LISTINGS_REGISTRY, []);
        if (! in_array($key, $keys, true)) {
            $keys[] = $key;
            Cache::forever(self::PRODUCT_LISTINGS_REGISTRY, $keys);
        }
    }
}
