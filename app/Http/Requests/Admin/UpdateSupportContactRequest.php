<?php

namespace App\Http\Requests\Admin;

use App\Enums\SupportContactType;
use App\Rules\SafeHttpUrl;
use App\Services\SecureUploadService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSupportContactRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'type' => ['required', Rule::in(SupportContactType::values())],
            'username' => ['nullable', 'string', 'max:255'],
            'link' => ['nullable', 'string', 'max:2048', new SafeHttpUrl],
            'enabled' => ['sometimes', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'qr_image' => ['nullable', 'file', 'max:'.SecureUploadService::MAX_QR_KB],
        ];
    }

    /** @return array<string, mixed> */
    public function contactAttributes(): array
    {
        $validated = $this->validated();

        return [
            'type' => $validated['type'],
            'username' => isset($validated['username']) ? strip_tags(trim((string) $validated['username'])) : null,
            'link' => isset($validated['link']) ? trim((string) $validated['link']) : null,
            'enabled' => $this->boolean('enabled'),
            'sort_order' => (int) ($validated['sort_order'] ?? 0),
        ];
    }
}
