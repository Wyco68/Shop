<?php

namespace Tests\Feature;

use App\Enums\SupportContactType;
use App\Models\SupportContact;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\BootstrapsStore;
use Tests\TestCase;

class SupportSettingsTest extends TestCase
{
    use BootstrapsStore;
    use RefreshDatabase;

    public function test_admin_can_create_support_contact_with_qr(): void
    {
        Storage::fake(config('filesystems.product_disk', 'public'));
        Cache::flush();

        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->post(route('admin.settings.support.store'), [
            'type' => SupportContactType::Telegram->value,
            'username' => '@shop_support',
            'link' => 'https://t.me/shop_support',
            'enabled' => '1',
            'sort_order' => 1,
            'qr_image' => UploadedFile::fake()->image('qr.png', 200, 200),
        ]);

        $response->assertRedirect(route('admin.settings.support.index'));
        $response->assertSessionHas('success');

        $contact = SupportContact::query()->first();
        $this->assertNotNull($contact);
        $this->assertSame('@shop_support', $contact->username);
        $this->assertNotNull($contact->qr_path);
        Storage::disk(config('filesystems.product_disk', 'public'))->assertExists($contact->qr_path);
    }

    public function test_rejects_unsafe_link_scheme(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->post(route('admin.settings.support.store'), [
            'type' => SupportContactType::Whatsapp->value,
            'link' => 'javascript:alert(1)',
            'enabled' => '1',
        ])->assertSessionHasErrors('link');
    }

    public function test_support_contacts_api_returns_enabled_only(): void
    {
        SupportContact::create([
            'type' => SupportContactType::Telegram,
            'username' => '@visible',
            'link' => 'https://t.me/visible',
            'enabled' => true,
            'sort_order' => 0,
        ]);
        SupportContact::create([
            'type' => SupportContactType::Facebook,
            'username' => '@hidden',
            'enabled' => false,
            'sort_order' => 1,
        ]);

        Cache::flush();

        $response = $this->getJson('/api/store/support-contacts');

        $response->assertOk()
            ->assertJsonCount(1, 'contacts')
            ->assertJsonPath('contacts.0.type', 'telegram')
            ->assertJsonPath('contacts.0.username', '@visible')
            ->assertJsonPath('contacts.0.link', 'https://t.me/visible');
    }

    public function test_contact_page_shows_enabled_contacts(): void
    {
        $this->bootstrapStore();

        SupportContact::create([
            'type' => SupportContactType::Line,
            'username' => 'line-id',
            'link' => 'https://line.me/ti/p/example',
            'enabled' => true,
        ]);

        $this->get(route('contact'))
            ->assertOk()
            ->assertSee('line-id', false);
    }
}
