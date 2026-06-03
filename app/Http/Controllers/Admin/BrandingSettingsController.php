<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\StoreSetting;
use App\Services\SecureUploadService;
use App\Services\StoreSettingsService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BrandingSettingsController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly StoreSettingsService $settings,
        private readonly SecureUploadService $uploads,
    ) {}

    public function edit(): View
    {
        $this->authorize('update', StoreSetting::class);

        $setting = $this->settings->get();

        return view('admin.settings.branding', [
            'setting' => $setting,
            'faviconUrl' => $this->settings->faviconUrl(),
            'logoUrl' => $this->settings->logoUrl(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $this->authorize('update', StoreSetting::class);

        $request->validate([
            'favicon' => ['nullable', 'file', 'max:'.SecureUploadService::MAX_BRANDING_KB],
            'logo' => ['nullable', 'file', 'max:'.SecureUploadService::MAX_BRANDING_KB],
        ]);

        $setting = $this->settings->get();
        $updates = [];

        try {
            if ($request->hasFile('favicon')) {
                $this->uploads->deleteIfExists($setting->favicon_path);
                $updates['favicon_path'] = $this->uploads->storeImage(
                    $request->file('favicon'),
                    'branding',
                    SecureUploadService::brandingMimes(),
                    SecureUploadService::MAX_BRANDING_KB,
                );
            }

            if ($request->hasFile('logo')) {
                $this->uploads->deleteIfExists($setting->logo_path);
                $updates['logo_path'] = $this->uploads->storeImage(
                    $request->file('logo'),
                    'branding',
                    SecureUploadService::brandingMimes(),
                    SecureUploadService::MAX_BRANDING_KB,
                );
            }
        } catch (\InvalidArgumentException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        if ($updates === []) {
            return back()->with('error', 'No branding changes to save.');
        }

        $this->settings->updateBranding($updates);

        return redirect()
            ->route('admin.settings.branding.edit')
            ->with('success', 'Branding updated successfully.');
    }
}
