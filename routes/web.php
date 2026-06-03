<?php

use App\Http\Controllers\CartController;
use App\Http\Controllers\SetupController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RefundController;
use App\Http\Controllers\Admin;
use Illuminate\Support\Facades\Route;

// First-run admin setup (disabled once an admin exists)
Route::middleware('no_admin_yet')->group(function () {
    Route::get('/setup', [SetupController::class, 'create'])->name('setup.create');
    Route::post('/setup', [SetupController::class, 'store'])->name('setup.store');
});

// Public routes
Route::middleware('redirect_admin')->group(function () {
    Route::get('/', [HomeController::class, 'index'])->name('home');
    Route::get('/products', [ProductController::class, 'index'])->name('products.index');
    Route::get('/products/{product:slug}', [ProductController::class, 'show'])->name('products.show');
    Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index');
});

// Authenticated routes
Route::middleware(['auth', 'redirect_admin'])->group(function () {
    // Cart (DB-based)
    Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
    Route::post('/cart', [CartController::class, 'add'])->name('cart.add');
    Route::patch('/cart', [CartController::class, 'update'])->name('cart.update');
    Route::delete('/cart', [CartController::class, 'remove'])->name('cart.remove');

    // Orders & checkout
    Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/create', [OrderController::class, 'create'])->name('orders.create');
    Route::post('/orders/checkout/method', [OrderController::class, 'selectPaymentMethod'])->name('orders.checkout.method');
    Route::get('/orders/checkout/instructions', [OrderController::class, 'showInstructions'])->name('orders.checkout.instructions');
    Route::post('/orders', [OrderController::class, 'store'])->middleware('throttle:checkout')->name('orders.store');
    Route::get('/orders/{order}/payment', [OrderController::class, 'payment'])->name('orders.payment');
    Route::post('/orders/{order}/payment', [OrderController::class, 'submitPayment'])->middleware('throttle:checkout')->name('orders.payment.submit');
    Route::get('/orders/{order}', [OrderController::class, 'show'])->name('orders.show');

    // Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Refund requests (user-facing)
    Route::post('/orders/{order}/refund', [RefundController::class, 'store'])->name('orders.refund.store');
});

Route::middleware(['auth'])->group(function () {
    // Notifications (Shared between admin and user)
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::patch('/notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.readAll');
    Route::patch('/notifications/{notification}/read', [NotificationController::class, 'markRead'])->name('notifications.read');
});

// Admin routes
Route::prefix('admin')->middleware(['auth', 'is_admin'])->name('admin.')->group(function () {
    Route::get('/', [Admin\DashboardController::class, 'index'])->name('dashboard');
    
    Route::get('notifications', [App\Http\Controllers\NotificationController::class, 'index'])->name('notifications.index');

    Route::resource('products', Admin\ProductController::class)->except(['show']);
    Route::resource('categories', Admin\CategoryController::class)->except(['show', 'create', 'edit']);

    Route::resource('payment-methods', Admin\PaymentMethodController::class)->except(['show']);

    Route::get('orders', [Admin\OrderController::class, 'index'])->name('orders.index');
    Route::get('orders/{order}', [Admin\OrderController::class, 'show'])->name('orders.show');
    Route::patch('orders/{order}/status', [Admin\OrderController::class, 'updateStatus'])->name('orders.updateStatus');
    Route::post('orders/{order}/process-payment', [Admin\OrderController::class, 'processPayment'])->name('orders.processPayment');
    Route::get('payments/{payment}/proof', [Admin\OrderController::class, 'downloadProof'])->name('payments.proof');
    Route::post('payments/{payment}/verify', [Admin\OrderController::class, 'verifyPayment'])->name('payments.verify');
    Route::post('payments/{payment}/reject', [Admin\OrderController::class, 'rejectPayment'])->name('payments.reject');

    // Analytics
    Route::get('analytics', [Admin\AnalyticsController::class, 'index'])->name('analytics.index');

    // Refund management
    Route::get('refunds', [Admin\RefundController::class, 'index'])->name('refunds.index');
    Route::post('refunds/{refundRequest}/approve', [Admin\RefundController::class, 'approve'])->name('refunds.approve');
    Route::post('refunds/{refundRequest}/reject', [Admin\RefundController::class, 'reject'])->name('refunds.reject');

    // User management
    Route::get('users', [Admin\UserController::class, 'index'])->name('users.index');
    Route::get('users/{id}', [Admin\UserController::class, 'show'])->name('users.show');

});

// Dashboard redirect for Breeze compatibility
Route::get('/dashboard', function () {
    return redirect()->route('home');
})->middleware('auth')->name('dashboard');

require __DIR__.'/auth.php';
