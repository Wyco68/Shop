<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;

class StoreCache
{
    public const CATEGORIES_ACTIVE = 'categories.active';

    public const CATEGORIES_INDEX = 'categories.index';

    public const HOME_FEATURED = 'home.featured';

    public const SETTINGS = 'cache:settings';

    public static function forgetSettings(): void
    {
        Cache::forget(self::SETTINGS);
    }

    public static function forgetCategories(): void
    {
        Cache::forget(self::CATEGORIES_ACTIVE);
        Cache::forget(self::CATEGORIES_INDEX);
    }

    public static function forgetProducts(): void
    {
        Cache::forget(self::HOME_FEATURED);
        // Product listing keys use prefix products.index.*
        // Flushed via pattern when using Redis; fallback forget common first page
        foreach (['products.index.default'] as $key) {
            Cache::forget($key);
        }
    }

    public static function productListingKey(array $filters, int $page = 1): string
    {
        ksort($filters);

        return 'products.index.'.md5(json_encode($filters).'.p'.$page);
    }
}
