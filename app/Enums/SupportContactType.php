<?php

namespace App\Enums;

enum SupportContactType: string
{
    case Telegram = 'telegram';
    case Whatsapp = 'whatsapp';
    case Facebook = 'facebook';
    case Line = 'line';
    case Instagram = 'instagram';
    case Email = 'email';
    case Phone = 'phone';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Telegram => 'Telegram',
            self::Whatsapp => 'WhatsApp',
            self::Facebook => 'Facebook',
            self::Line => 'LINE',
            self::Instagram => 'Instagram',
            self::Email => 'Email',
            self::Phone => 'Phone',
            self::Other => 'Other',
        };
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
