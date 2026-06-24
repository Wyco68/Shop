<?php

namespace App\Services;

use App\Enums\CurrencyPosition;
use App\Models\StoreSetting;
use App\Support\StoreCache;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class StoreSettingsService
{
    public function get(): StoreSetting
    {
        $setting = Cache::remember(StoreCache::SETTINGS, 3600, function () {
            $row = StoreSetting::query()->first();

            if ($row) {
                return $this->ensureCurrencyDefaults($row);
            }

            return $this->ensureCurrencyDefaults(StoreSetting::query()->create());
        });

        return $this->ensureCurrencyDefaults($setting);
    }

    public function isCurrencyLocked(): bool
    {
        return (bool) $this->get()->currency_locked;
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
     * First-run bootstrap: store name + currency (locked immediately).
     *
     * @throws \RuntimeException
     */
    public function initializeAtSetup(
        string $storeName,
        string $currencyCode,
        string $currencySymbol,
        CurrencyPosition $position,
    ): StoreSetting {
        if (StoreSetting::query()->where('currency_locked', true)->exists()) {
            throw new \RuntimeException('Store setup has already completed and currency is locked.');
        }

        if (StoreSetting::query()->whereNotNull('store_name')->where('store_name', '!=', '')->exists()) {
            throw new \RuntimeException('Store name is already set and cannot be changed via bootstrap.');
        }

        StoreSetting::query()->delete();

        $setting = StoreSetting::query()->create([
            'store_name' => trim($storeName),
            'currency_code' => strtoupper($currencyCode),
            'currency_symbol' => $currencySymbol,
            'currency_position' => $position->value,
            'currency_locked' => true,
        ]);

        $this->refreshSettingsCache($setting->fresh());

        config(['shop.name' => $setting->store_name]);

        return $setting;
    }

    public function updateBranding(array $attributes): StoreSetting
    {
        unset($attributes['store_name'], $attributes['currency_code'], $attributes['currency_symbol'], $attributes['currency_position'], $attributes['currency_locked']);

        $setting = StoreSetting::query()->first() ?? StoreSetting::query()->create();
        $setting->fill($attributes);
        $setting->save();

        return $this->refreshSettingsCache($setting->fresh());
    }

    public function forceChangeCurrency(string $currencyCode, string $currencySymbol, CurrencyPosition $position): StoreSetting
    {
        $setting = StoreSetting::query()->first() ?? StoreSetting::query()->create();

        StoreSetting::withoutCurrencyLock(function () use ($setting, $currencyCode, $currencySymbol, $position): void {
            $setting->update([
                'currency_code' => strtoupper($currencyCode),
                'currency_symbol' => $currencySymbol,
                'currency_position' => $position->value,
                'currency_locked' => true,
            ]);
        });

        Log::warning('Store currency force-changed', [
            'currency_code' => $setting->currency_code,
            'currency_symbol' => $setting->currency_symbol,
        ]);

        return $this->refreshSettingsCache($setting->fresh());
    }

    public function forceChangeStoreName(string $storeName): StoreSetting
    {
        $setting = StoreSetting::query()->first() ?? StoreSetting::query()->create();

        $setting->update(['store_name' => trim($storeName)]);

        Log::warning('Store name force-changed', [
            'store_name' => $setting->store_name,
        ]);

        return $this->refreshSettingsCache($setting->fresh());
    }

    public function refreshSettingsCache(?StoreSetting $setting = null): StoreSetting
    {
        StoreCache::forgetSettings();

        $setting = $this->ensureCurrencyDefaults(
            $setting ?? StoreSetting::query()->first() ?? StoreSetting::query()->create()
        );

        Cache::put(StoreCache::SETTINGS, $setting, 3600);
        $this->syncCurrencyToConfig($setting);

        return $setting;
    }

    public function syncCurrencyToConfig(?StoreSetting $setting = null): void
    {
        $setting = $this->ensureCurrencyDefaults($setting ?? $this->get());

        config([
            'shop.currency' => $setting->currency_code,
            'shop.currency_symbol' => $setting->currency_symbol,
            'shop.currency_position' => $setting->currency_position,
        ]);
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

    private function ensureCurrencyDefaults(StoreSetting $setting): StoreSetting
    {
        if ($setting->currency_locked) {
            return $setting;
        }

        $defaults = [
            'currency_code' => (string) config('shop.currency', 'USD'),
            'currency_symbol' => (string) config('shop.currency_symbol', '$'),
            'currency_position' => (string) config('shop.currency_position', 'before'),
        ];

        $changed = false;

        foreach ($defaults as $key => $value) {
            if (! filled($setting->{$key})) {
                $setting->{$key} = $value;
                $changed = true;
            }
        }

        if ($changed && $setting->exists) {
            $setting->save();
            StoreCache::forgetSettings();
        }

        return $setting;
    }
}
