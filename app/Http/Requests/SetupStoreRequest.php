<?php

namespace App\Http\Requests;

use App\Enums\CurrencyPosition;
use App\Services\AdminBootstrapService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SetupStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'store_name' => ['required', 'string', 'max:255'],
            'currency_code' => ['required', 'string', 'size:3', 'alpha:ascii', 'uppercase'],
            'currency_symbol' => ['required', 'string', 'min:1', 'max:8'],
            'currency_position' => ['required', Rule::in(CurrencyPosition::values())],
            'name' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
            'password' => AdminBootstrapService::passwordRules(),
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('currency_code')) {
            $this->merge([
                'currency_code' => strtoupper((string) $this->input('currency_code')),
            ]);
        }
    }
}
