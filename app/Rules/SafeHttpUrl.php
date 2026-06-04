<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class SafeHttpUrl implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || $value === '') {
            return;
        }

        if (! is_string($value)) {
            $fail('The :attribute must be a valid URL.');

            return;
        }

        $trimmed = trim($value);

        if (preg_match('/[\x00-\x1F\x7F]/', $trimmed) || preg_match('/javascript:|data:|vbscript:/i', $trimmed)) {
            $fail('The :attribute is not allowed.');

            return;
        }

        if (! filter_var($trimmed, FILTER_VALIDATE_URL)) {
            $fail('The :attribute must be a valid URL.');

            return;
        }

        $scheme = strtolower((string) parse_url($trimmed, PHP_URL_SCHEME));

        if (! in_array($scheme, ['http', 'https'], true)) {
            $fail('The :attribute must use http or https.');
        }
    }
}
