<?php

namespace App\Enums;

enum PaymentMethodType: string
{
    case Bank = 'bank';
    case Mobile = 'mobile';
    case Crypto = 'crypto';

    public function label(): string
    {
        return match ($this) {
            self::Bank => 'Bank transfer',
            self::Mobile => 'Mobile payment',
            self::Crypto => 'Cryptocurrency',
        };
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
