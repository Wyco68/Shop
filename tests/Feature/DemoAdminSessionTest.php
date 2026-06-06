<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\DemoAdminSession;
use App\Models\User;
use App\Services\AdminBootstrapService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemoAdminSessionTest extends TestCase
{
    use RefreshDatabase;

    public function test_category_created_by_demo_admin_is_removed_on_logout(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post(route('admin.categories.store'), ['name' => 'Brakes', 'is_active' => 1])
            ->assertRedirect(route('admin.categories.index'));

        $this->assertDatabaseHas('categories', ['name' => 'Brakes']);

        $this->post(route('logout'));

        $this->assertDatabaseMissing('categories', ['name' => 'Brakes']);
    }

    public function test_category_updated_by_demo_admin_is_restored_on_logout(): void
    {
        $admin = User::factory()->admin()->create();
        $category = Category::factory()->create(['name' => 'Original Name']);

        $this->actingAs($admin)
            ->put(route('admin.categories.update', $category), ['name' => 'Changed Name', 'is_active' => 1])
            ->assertRedirect(route('admin.categories.index'));

        $this->assertDatabaseHas('categories', ['id' => $category->id, 'name' => 'Changed Name']);

        $this->post(route('logout'));

        $this->assertDatabaseHas('categories', ['id' => $category->id, 'name' => 'Original Name']);
    }

    public function test_category_deleted_by_demo_admin_is_restored_on_logout(): void
    {
        $admin = User::factory()->admin()->create();
        $category = Category::factory()->create(['name' => 'To Be Deleted']);

        $this->actingAs($admin)
            ->delete(route('admin.categories.destroy', $category))
            ->assertRedirect(route('admin.categories.index'));

        $this->assertDatabaseMissing('categories', ['id' => $category->id]);

        $this->post(route('logout'));

        $this->assertDatabaseHas('categories', ['id' => $category->id, 'name' => 'To Be Deleted']);
    }

    public function test_owner_changes_are_never_reverted_on_logout(): void
    {
        User::factory()->admin()->create();
        $owner = app(AdminBootstrapService::class)->createOwner([
            'email' => 'herik.dev06@gmail.com',
            'password' => 'OwnerPassword1!Secure',
        ]);

        $this->actingAs($owner)
            ->post(route('admin.categories.store'), ['name' => 'Permanent Category', 'is_active' => 1])
            ->assertRedirect(route('admin.categories.index'));

        $this->post(route('logout'));

        $this->assertDatabaseHas('categories', ['name' => 'Permanent Category']);
        $this->assertDatabaseCount('demo_admin_sessions', 0);
    }

    public function test_demo_admin_session_expires_and_reverts_changes_automatically(): void
    {
        config(['admin.demo.session_minutes' => 30]);

        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post(route('admin.categories.store'), ['name' => 'Expiring Category', 'is_active' => 1])
            ->assertRedirect(route('admin.categories.index'));

        $session = DemoAdminSession::sole();
        $session->forceFill(['expires_at' => now()->subMinute()])->save();

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertRedirect(route('login'));
        $this->assertGuest();
        $this->assertDatabaseMissing('categories', ['name' => 'Expiring Category']);
        $this->assertNotNull($session->fresh()->reverted_at);
    }
}
