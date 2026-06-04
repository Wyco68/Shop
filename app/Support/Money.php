<?php

namespace App\Support;

use App\Enums\CurrencyPosition;

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

    public static function position(): CurrencyPosition
    {
        $position = (string) config('shop.currency_position', 'before');

        return CurrencyPosition::tryFrom($position) ?? CurrencyPosition::Before;
    }

    public static function format(float|int|string|null $amount, int $decimals = 2): string
    {
        $formatted = number_format((float) ($amount ?? 0), $decimals);
        $symbol = self::symbol();

        if (self::position() === CurrencyPosition::Before) {
            return $symbol.$formatted;
        }

        return $formatted.$symbol;
    }
}
