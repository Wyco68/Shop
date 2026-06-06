<?php

namespace App\Support;

use Illuminate\Validation\Rules\Password;

class PasswordRules
{
    public static function defaults(): Password
    {
        return Password::min(12)->mixedCase()->numbers()->symbols();
    }

    /**
     * @return array<int, mixed>
     */
    public static function validationRules(bool $confirmed = true): array
    {
        $rules = ['required', self::defaults()];

        if ($confirmed) {
            $rules[] = 'confirmed';
        }

        return $rules;
    }
}
