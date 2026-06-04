<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;

class StoreCache
{
    public const CATEGORIES_ACTIVE = 'categories.active';

    public const CATEGORIES_INDEX = 'categories.index';

    public const HOME_FEATURED = 'home.featured';

    public const SETTINGS = 'cache:settings';

    public const PRODUCT_LISTINGS_REGISTRY = 'products.index.registry';

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
