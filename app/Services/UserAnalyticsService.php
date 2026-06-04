<?php

namespace App\Services;

use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class UserAnalyticsService
{
    /**
     * Valid order statuses that count towards revenue / total_spent.
     * Mirrors AnalyticsService::VALID_STATUSES.
     */
    private const REVENUE_STATUSES = [
        Order::STATUS_PAID,        // confirmed ≈ paid
        Order::STATUS_PROCESSING,
        Order::STATUS_SHIPPED,
        Order::STATUS_COMPLETED,   // delivered ≈ completed
    ];

    /**
     * All statuses that appear in order_stats breakdown.
     */
    private const TRACKED_STATUSES = [
        Order::STATUS_PENDING_PAYMENT,
        Order::STATUS_PENDING,
        Order::STATUS_PAID,
        Order::STATUS_PROCESSING,
        Order::STATUS_SHIPPED,
        Order::STATUS_COMPLETED,
        Order::STATUS_CANCELLED,
        Order::STATUS_REFUNDED,
        'return_requested',
        'returned',
    ];

    /**
     * Build the full user profile data object for admin view.
     * All aggregates are computed via SELECT in a single efficient pass.
     *
     * @param int $userId
     * @return array
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function getUserProfile(int $userId): array
    {
        // Cache key per-user — short TTL to keep data fresh for admins
        $cacheKey = "admin.user_profile.{$userId}";

        return Cache::remember($cacheKey, now()->addMinutes(5), function () use ($userId) {
            return $this->buildProfile($userId);
        });
    }

    /**
     * Invalidate the cached profile for a user (call after writes).
     */
    public function forgetCache(int $userId): void
    {
        Cache::forget("admin.user_profile.{$userId}");
    }

    // ----------------------------------------------------------------
    // Private: Core build logic
    // ----------------------------------------------------------------

    private function buildProfile(int $userId): array
    {
        // 1. Fetch user — abort if not found
        $user = User::findOrFail($userId);

        // 2. Single aggregate query — all metrics in one DB round-trip
        $metrics = $this->fetchMetrics($userId);

        // 3. Order status breakdown — GROUP BY in one query
        $orderStats = $this->fetchOrderStats($userId);

        // 4. Recent orders — latest 5, no N+1 (eager load payment method)
        $recentOrders = $this->fetchRecentOrders($userId);

        return [
            'user'         => $this->formatUser($user),
            'metrics'      => $metrics,
            'order_stats'  => $orderStats,
            'recent_orders' => $recentOrders,
        ];
    }

    // ----------------------------------------------------------------
    // Private: Metrics (single aggregate query)
    // ----------------------------------------------------------------

    private function fetchMetrics(int $userId): array
    {
        // One query: total_orders, total_spent (valid statuses), last_order_date
        $aggregate = DB::table('orders')
            ->where('user_id', $userId)
            ->selectRaw('
                COUNT(*) as total_orders,
                COALESCE(SUM(CASE WHEN status IN (' . $this->inPlaceholders(self::REVENUE_STATUSES) . ') THEN total ELSE 0 END), 0) as total_spent,
                MAX(created_at) as last_order_date
            ', array_merge(self::REVENUE_STATUSES))
            ->first();

        $totalOrders = (int) ($aggregate->total_orders ?? 0);
        $totalSpent  = (float) ($aggregate->total_spent ?? 0.0);

        return [
            'total_orders'        => $totalOrders,
            'total_spent'         => round($totalSpent, 2),
            'average_order_value' => $totalOrders > 0 ? round($totalSpent / $totalOrders, 2) : 0.0,
            'last_order_date'     => $aggregate->last_order_date,
            'lifetime_value'      => round($totalSpent, 2), // alias, extensible
        ];
    }

    // ----------------------------------------------------------------
    // Private: Order status breakdown (GROUP BY)
    // ----------------------------------------------------------------

    private function fetchOrderStats(int $userId): array
    {
        // One GROUP BY query — no N+1, no loading all orders into memory
        $rows = DB::table('orders')
            ->where('user_id', $userId)
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        // Normalize — ensure all tracked statuses present with 0 default
        $stats = [];
        foreach (self::TRACKED_STATUSES as $status) {
            $stats[$status] = (int) ($rows[$status] ?? 0);
        }

        // Add human-readable aliases for the view
        return [
            'pending'          => $stats[Order::STATUS_PENDING_PAYMENT] + $stats[Order::STATUS_PENDING],
            'confirmed'        => $stats[Order::STATUS_PAID],
            'processing'       => $stats[Order::STATUS_PROCESSING],
            'shipped'          => $stats[Order::STATUS_SHIPPED],
            'delivered'        => $stats[Order::STATUS_COMPLETED],
            'cancelled'        => $stats[Order::STATUS_CANCELLED],
            'refunded'         => $stats[Order::STATUS_REFUNDED],
            'return_requested' => $stats['return_requested'],
            'returned'         => $stats['returned'],
            // raw map for flexibility
            '_raw'             => $stats,
        ];
    }

    // ----------------------------------------------------------------
    // Private: Recent orders (limit 5)
    // ----------------------------------------------------------------

    private function fetchRecentOrders(int $userId): array
    {
        return Order::where('user_id', $userId)
            ->select('id', 'status', 'total', 'created_at')
            ->orderByDesc('created_at')
            ->limit(5)
            ->get()
            ->map(fn ($o) => [
                'id'         => $o->id,
                'status'     => $o->status,
                'total'      => (float) $o->total,
                'created_at' => $o->created_at,
            ])
            ->toArray();
    }

    // ----------------------------------------------------------------
    // Private: Helpers
    // ----------------------------------------------------------------

    private function formatUser(User $user): array
    {
        return [
            'id'         => $user->id,
            'name'       => $user->name,
            'email'      => $user->email,
            'role'       => $user->role,
            'phone_num'  => $user->phone_num,
            'address'    => $user->address,
            'created_at' => $user->created_at,
            'is_active'  => $user->is_active,
        ];
    }

    /**
     * Build SQL IN placeholders: ?,?,? for n values.
     */
    private function inPlaceholders(array $values): string
    {
        return implode(',', array_fill(0, count($values), '?'));
    }
}
