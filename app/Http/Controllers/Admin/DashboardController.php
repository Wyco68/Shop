<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Services\InventoryService;
use App\Support\StoreCache;
use Illuminate\Support\Facades\Cache;

class DashboardController extends Controller
{
    public function __construct(private readonly InventoryService $inventoryService)
    {}

    public function index()
    {
        $stats = Cache::remember(StoreCache::DASHBOARD_STATS, 30, function () {
            return [
                'total_orders' => Order::count(),
                'pending_orders' => Order::whereIn('status', [
                    Order::STATUS_PENDING,
                    Order::STATUS_PENDING_PAYMENT,
                ])->count(),
                'revenue' => Order::whereIn('status', ['paid', 'confirmed', 'shipped'])->sum('total'),
                'pending_payments' => Payment::where('status', Payment::STATUS_PENDING)
                    ->whereNotNull('proof_path')
                    ->count(),
            ];
        });

        $lowStock = $this->inventoryService->getLowStockVariants();

        $recentOrders = Order::with('user', 'latestPayment')
            ->latest()
            ->take(10)
            ->get();

        $setupHints = Cache::remember(StoreCache::DASHBOARD_SETUP_HINTS, 30, function () {
            return [
                'needs_categories' => Category::count() === 0,
                'needs_products' => Product::count() === 0,
                'needs_payment_methods' => PaymentMethod::active()->count() === 0,
            ];
        });

        return view('admin.dashboard', compact('stats', 'lowStock', 'recentOrders', 'setupHints'));
    }
}
