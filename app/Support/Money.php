<?php

namespace App\Support;

class Money
{
    public static function symbol(): string
    {
        return (string) config('shop.currency_symbol', '$');
    }

    public static function currency(): string
    {
        return (string) config('shop.currency', 'USD');
    }

    public static function format(float|int|string|null $amount, int $decimals = 2): string
    {
        return self::symbol().number_format((float) ($amount ?? 0), $decimals);
    }
}
