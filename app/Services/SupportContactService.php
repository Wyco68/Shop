<?php

namespace App\Services;

use App\Models\SupportContact;
use App\Support\StoreCache;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class SupportContactService
{
    /** @return Collection<int, SupportContact> */
    public function enabled(): Collection
    {
        return $this->allCached()->where('enabled', true)->values();
    }

    /** @return Collection<int, SupportContact> */
    public function allCached(): Collection
    {
        return Cache::remember(StoreCache::SUPPORT_CONTACTS, 3600, function () {
            return SupportContact::query()
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get();
        });
    }

    public function forgetCache(): void
    {
        StoreCache::forgetSupportContacts();
    }

    public function qrUrl(?string $path): ?string
    {
        return $path
            ? app(SecureUploadService::class)->url($path)
            : null;
    }
}
