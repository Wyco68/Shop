<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use RuntimeException;

class StoreSetting extends Model
{
    private static bool $allowCurrencyOverride = false;

    protected $fillable = [
        'store_name',
        'favicon_path',
        'logo_path',
        'currency_code',
        'currency_symbol',
        'currency_position',
        'currency_locked',
    ];

    protected function casts(): array
    {
        return [
            'currency_locked' => 'boolean',
        ];
    }

    /**
     * Bypass currency lock (CLI force-change only).
     *
     * @template TReturn
     *
     * @param  callable(): TReturn  $callback
     * @return TReturn
     */
    public static function withoutCurrencyLock(callable $callback): mixed
    {
        static::$allowCurrencyOverride = true;

        try {
            return $callback();
        } finally {
            static::$allowCurrencyOverride = false;
        }
    }

    protected static function booted(): void
    {
        static::saving(function (StoreSetting $setting): void {
            if (static::$allowCurrencyOverride || ! $setting->exists) {
                return;
            }

            $wasLocked = (bool) $setting->getOriginal('currency_locked');

            if (! $wasLocked && $setting->currency_locked) {
                return;
            }

            if (! $wasLocked) {
                return;
            }

            foreach (['currency_code', 'currency_symbol', 'currency_position'] as $field) {
                if ($setting->isDirty($field)) {
                    throw new RuntimeException('Currency cannot be changed after setup.');
                }
            }

            if ($setting->isDirty('currency_locked') && ! $setting->currency_locked) {
                throw new RuntimeException('Currency lock cannot be removed after setup.');
            }
        });
    }
}
