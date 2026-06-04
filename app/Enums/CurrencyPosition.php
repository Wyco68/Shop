<?php

namespace App\Enums;

enum CurrencyPosition: string
{
    case Before = 'before';
    case After = 'after';

    public function label(): string
    {
        return match ($this) {
            self::Before => 'Before amount',
            self::After => 'After amount',
        };
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
