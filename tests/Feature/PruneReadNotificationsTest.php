<?php

namespace Tests\Feature;

use App\Models\Notification;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PruneReadNotificationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_prunes_read_notifications_older_than_retention(): void
    {
        $user = User::factory()->create();

        $oldRead = Notification::query()->create([
            'user_id' => $user->id,
            'type' => Notification::TYPE_ORDER_STATUS_UPDATED,
            'title' => 'Old',
            'message' => 'Old message',
            'read_at' => now()->subDays(11),
            'created_at' => now()->subDays(12),
        ]);

        $recentRead = Notification::query()->create([
            'user_id' => $user->id,
            'type' => Notification::TYPE_ORDER_STATUS_UPDATED,
            'title' => 'Recent',
            'message' => 'Recent message',
            'read_at' => now()->subDays(5),
            'created_at' => now()->subDays(6),
        ]);

        $unread = Notification::query()->create([
            'user_id' => $user->id,
            'type' => Notification::TYPE_ORDER_STATUS_UPDATED,
            'title' => 'Unread',
            'message' => 'Unread message',
            'read_at' => null,
            'created_at' => now()->subDays(30),
        ]);

        $deleted = app(NotificationService::class)->pruneReadOlderThan(10);

        $this->assertSame(1, $deleted);
        $this->assertDatabaseMissing('notifications', ['id' => $oldRead->id]);
        $this->assertDatabaseHas('notifications', ['id' => $recentRead->id]);
        $this->assertDatabaseHas('notifications', ['id' => $unread->id]);
    }

    public function test_prune_command_runs_successfully(): void
    {
        $user = User::factory()->create();

        Notification::query()->create([
            'user_id' => $user->id,
            'type' => Notification::TYPE_ORDER_STATUS_UPDATED,
            'title' => 'Stale',
            'message' => 'Stale message',
            'read_at' => now()->subDays(15),
            'created_at' => now()->subDays(16),
        ]);

        $this->artisan('notifications:prune-read')
            ->assertSuccessful();

        $this->assertDatabaseCount('notifications', 0);
    }
}
