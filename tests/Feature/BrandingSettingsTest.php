<?php

namespace Tests\Feature;

use App\Models\StoreSetting;
use App\Models\User;
use App\Support\StoreCache;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BrandingSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_upload_branding(): void
    {
        Storage::fake('public');

        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->put(route('admin.settings.branding.update'), [
            'logo' => UploadedFile::fake()->image('logo.png', 100, 40)->size(100),
        ]);

        $response->assertRedirect(route('admin.settings.branding.edit'));
        $response->assertSessionHas('success');

        $setting = StoreSetting::query()->first();
        $this->assertNotNull($setting->logo_path);
        Storage::disk('public')->assertExists($setting->logo_path);
        $this->assertFalse(Cache::has(StoreCache::SETTINGS));
    }
}
