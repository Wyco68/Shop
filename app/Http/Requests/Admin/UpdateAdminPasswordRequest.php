<?php

namespace App\Http\Requests\Admin;

use App\Support\PasswordRules;
use Illuminate\Foundation\Http\FormRequest;

class UpdateAdminPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $passwordRule = PasswordRules::defaults();

        if (config('admin.password.check_breached', true)) {
            $passwordRule = $passwordRule->uncompromised();
        }

        return [
            'current_password' => ['required', 'string', 'current_password'],
            'password' => ['required', 'confirmed', $passwordRule],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'password.uncompromised' => 'This password has appeared in a data breach. Choose a different password.',
        ];
    }
}
