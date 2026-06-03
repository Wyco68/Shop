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
            return StoreSetting::query()->firstOrCreate(['id' => 1]);
        });
    }

    public function updateBranding(array $attributes): StoreSetting
    {
        $setting = StoreSetting::query()->firstOrCreate(['id' => 1]);
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
