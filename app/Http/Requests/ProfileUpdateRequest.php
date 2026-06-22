<?php

namespace App\Http\Requests;

use App\Models\User;
use App\Support\PasswordRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'role' => ['prohibited'],
            'is_admin' => ['prohibited'],
            'is_active' => ['prohibited'],
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($this->user()->id),
            ],
            'phone_num' => ['nullable', 'string', 'max:20'],
            'address'   => ['nullable', 'string', 'max:255'],
            'notify_order_status_email' => ['sometimes', 'boolean'],
        ];

        if (! $this->user()->isAdmin() && $this->filled('password')) {
            $rules['current_password'] = ['required', 'current_password'];
            $rules['password'] = PasswordRules::validationRules();
        }

        return $rules;
    }
}
