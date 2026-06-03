<?php

namespace App\Services;

use App\Models\StoreSetting;
use App\Support\StoreCache;
use Illuminate\Support\Facades\Cache;

class StoreSettingsService
{
    public function get(): StoreSetting
    {
        return Cache::remember(StoreCache::SETTINGS, 3600, function () {
            return StoreSetting::query()->first()
                ?? StoreSetting::query()->create();
        });
    }

    public function displayName(): string
    {
        $custom = $this->get()->store_name;

        if (filled($custom)) {
            return $custom;
        }

        return (string) config('shop.name');
    }

    /**
     * Set the shop name once during first-run bootstrap (setup / init-admin).
     */
    public function initializeStoreName(string $storeName): StoreSetting
    {
        if (StoreSetting::query()->whereNotNull('store_name')->where('store_name', '!=', '')->exists()) {
            throw new \RuntimeException('Store name is already set and cannot be changed via bootstrap.');
        }

        StoreSetting::query()->delete();

        $setting = StoreSetting::query()->create([
            'store_name' => trim($storeName),
        ]);

        StoreCache::forgetSettings();
        config(['shop.name' => $setting->store_name]);

        return $setting;
    }

    public function updateBranding(array $attributes): StoreSetting
    {
        unset($attributes['store_name']);

        $setting = $this->get();
        $setting->fill($attributes);
        $setting->save();

        StoreCache::forgetSettings();

        return $setting;
    }

    public function faviconUrl(): ?string
    {
        $path = $this->get()->favicon_path;

        return $path
            ? app(SecureUploadService::class)->url($path)
            : asset('images/logo.png');
    }

    public function logoUrl(): ?string
    {
        $path = $this->get()->logo_path;

        return $path
            ? app(SecureUploadService::class)->url($path)
            : asset('images/logo.png');
    }

}
