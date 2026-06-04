<?php

namespace App\Http\Resources;

use App\Models\SupportContact;
use App\Services\SupportContactService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin SupportContact */
class SupportContactResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $service = app(SupportContactService::class);

        return [
            'id' => $this->id,
            'type' => $this->type instanceof \BackedEnum ? $this->type->value : $this->type,
            'username' => $this->username,
            'link' => $this->link,
            'qr_url' => $service->qrUrl($this->qr_path),
            'enabled' => (bool) $this->enabled,
            'sort_order' => (int) $this->sort_order,
        ];
    }
}
