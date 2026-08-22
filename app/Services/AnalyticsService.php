<?php

namespace App\Services;

use App\Models\Order;
use App\Models\User;
use App\Support\StoreCache;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class AnalyticsService
{
    /**
     * Valid order statuses that count towards earnings.
     * Maps to: confirmed (paid), shipped, delivered (completed).
     */
    private const VALID_STATUSES = [
        Order::STATUS_PAID,       // confirmed
        Order::STATUS_PROCESSING,
        Order::STATUS_SHIPPED,
        Order::STATUS_COMPLETED,  // delivered
    ];

    /**
     * Get total earnings for a given year/month from valid orders only.
     */
    public function getMonthlyEarnings(int $year, int $month): float
    {
        return Cache::remember(StoreCache::ANALYTICS_MONTHLY_EARNINGS.":{$year}:{$month}", 60, function () use ($year, $month) {
            return (float) Order::whereIn('status', self::VALID_STATUSES)
                ->whereYear('created_at', $year)
                ->whereMonth('created_at', $month)
                ->sum('total');
        });
    }

    /**
     * Get total earnings for each month of a year.
     * Returns an array indexed 1–12 with total earnings per month.
     */
    public function getYearlyEarningsByMonth(int $year): array
    {
        return Cache::remember(StoreCache::ANALYTICS_YEARLY_EARNINGS.":{$year}", 60, function () use ($year) {
            // MONTH() is MySQL-only; the app also runs on Postgres (production) and
            // SQLite (tests), each with a different month-extraction function.
            $month = match (DB::connection()->getDriverName()) {
                'pgsql' => 'CAST(EXTRACT(MONTH FROM created_at) AS INTEGER)',
                'sqlite' => "CAST(strftime('%m', created_at) AS INTEGER)",
                default => 'MONTH(created_at)',
            };

            $rows = Order::whereIn('status', self::VALID_STATUSES)
                ->whereYear('created_at', $year)
                ->selectRaw("{$month} as month, SUM(total) as total")
                ->groupBy('month')
                ->pluck('total', 'month');

            $result = [];
            for ($m = 1; $m <= 12; $m++) {
                $result[$m] = (float) ($rows[$m] ?? 0);
            }

            return $result;
        });
    }

    /**
     * Aggregate total spending per user (from valid orders only).
     * Returns a collection of [{user_id, name, email, total_spent}].
     */
    public function getUserSpending(): Collection
    {
        return Cache::remember(StoreCache::ANALYTICS_USER_SPENDING, 60, function () {
            return DB::table('orders')
                ->join('users', 'orders.user_id', '=', 'users.id')
                ->whereIn('orders.status', self::VALID_STATUSES)
                ->selectRaw('users.id as user_id, users.name, users.email, SUM(orders.total) as total_spent')
                ->groupBy('users.id', 'users.name', 'users.email')
                ->orderByDesc('total_spent')
                ->get();
        });
    }

    /**
     * Get paginated order history for a specific user, with optional filters.
     *
     * @param array $filters Supports: status, date_from, date_to
     */
    public function getOrderHistory(User $user, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Order::where('user_id', $user->id)
            ->with('orderItems', 'latestPayment', 'paymentMethod')
            ->orderByDesc('created_at');

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        return $query->paginate($perPage);
    }

    /**
     * Get dashboard summary stats for admin.
     */
    public function getDashboardStats(): array
    {
        return Cache::remember(StoreCache::ANALYTICS_DASHBOARD_STATS, 60, function () {
            $now = now();

            return [
                'monthly_earnings'  => $this->getMonthlyEarnings($now->year, $now->month),
                'total_orders'      => Order::count(),
                'pending_orders'    => Order::where('status', Order::STATUS_PENDING_PAYMENT)->count(),
                'total_users'       => User::where('role', 'user')->count(),
            ];
        });
    }
}
