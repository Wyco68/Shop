<?php

namespace Tests\Feature;

use App\Events\OrderPlaced;
use App\Events\OrderStatusUpdated;
use App\Events\RefundApproved;
use App\Events\RefundRejected;
use App\Events\RefundRequested;
use App\Listeners\NotifyAdminOrderPlaced;
use App\Listeners\NotifyAdminRefundRequested;
use App\Listeners\NotifyUserOrderStatusUpdated;
use App\Listeners\NotifyUserRefundApproved;
use App\Listeners\NotifyUserRefundRejected;
use App\Models\Category;
use App\Models\Inventory;
use App\Models\Notification;
use App\Models\Order;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\RefundRequest;
use App\Models\User;
use App\Services\CartService;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    private function createOrderForUser(User $user): Order
    {
        $category  = Category::factory()->create();
        $product   = Product::factory()->create(['category_id' => $category->id, 'base_price' => 10.00]);
        $variant   = ProductVariant::factory()->create(['product_id' => $product->id]);
        Inventory::factory()->create(['variant_id' => $variant->id, 'stock_quantity' => 50, 'reserved_quantity' => 0]);
        $pmId      = PaymentMethod::factory()->create()->id;

        $cartService = app(CartService::class);
        $cart        = $cartService->getOrCreateCart($user);
        $cartService->addItem($cart, $variant->id, 1);

        return app(OrderService::class)->createFromCart($user, $cart, $pmId);
    }

    public function test_admin_receives_notification_on_order_placed(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user  = User::factory()->create(['role' => 'user']);

        $order = $this->createOrderForUser($user);

        app(NotifyAdminOrderPlaced::class)->handle(new OrderPlaced($order));

        $this->assertDatabaseHas('notifications', [
            'user_id' => $admin->id,
            'type'    => Notification::TYPE_ORDER_PLACED,
        ]);
    }

    public function test_user_receives_notification_on_status_update(): void
    {
        $user  = User::factory()->create(['role' => 'user']);
        $order = $this->createOrderForUser($user);

        app(NotifyUserOrderStatusUpdated::class)->handle(
            new OrderStatusUpdated($order, Order::STATUS_PENDING_PAYMENT, Order::STATUS_PAID)
        );

        $this->assertDatabaseHas('notifications', [
            'user_id' => $user->id,
            'type'    => Notification::TYPE_ORDER_STATUS_UPDATED,
        ]);
    }

    public function test_admin_receives_notification_on_refund_requested(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user  = User::factory()->create(['role' => 'user']);
        $order = $this->createOrderForUser($user);

        $refund = RefundRequest::create([
            'order_id' => $order->id,
            'user_id'  => $user->id,
            'reason'   => 'Item arrived damaged.',
            'status'   => RefundRequest::STATUS_PENDING,
        ]);

        app(NotifyAdminRefundRequested::class)->handle(new RefundRequested($refund));

        $this->assertDatabaseHas('notifications', [
            'user_id' => $admin->id,
            'type'    => Notification::TYPE_REFUND_REQUESTED,
        ]);
    }

    public function test_user_receives_notification_on_refund_approved(): void
    {
        $user  = User::factory()->create(['role' => 'user']);
        $order = $this->createOrderForUser($user);

        $refund = RefundRequest::create([
            'order_id' => $order->id,
            'user_id'  => $user->id,
            'reason'   => 'Not as described.',
            'status'   => RefundRequest::STATUS_APPROVED,
        ]);

        app(NotifyUserRefundApproved::class)->handle(new RefundApproved($refund));

        $this->assertDatabaseHas('notifications', [
            'user_id' => $user->id,
            'type'    => Notification::TYPE_REFUND_APPROVED,
        ]);
    }

    public function test_user_receives_notification_on_refund_rejected(): void
    {
        $user  = User::factory()->create(['role' => 'user']);
        $order = $this->createOrderForUser($user);

        $refund = RefundRequest::create([
            'order_id' => $order->id,
            'user_id'  => $user->id,
            'reason'   => 'Changed my mind.',
            'status'   => RefundRequest::STATUS_REJECTED,
            'admin_note' => 'Policy does not allow change of mind refunds.',
        ]);

        app(NotifyUserRefundRejected::class)->handle(new RefundRejected($refund));

        $this->assertDatabaseHas('notifications', [
            'user_id' => $user->id,
            'type'    => Notification::TYPE_REFUND_REJECTED,
        ]);
    }

    public function test_event_listeners_are_registered(): void
    {
        $this->assertTrue(Event::hasListeners(OrderPlaced::class));
        $this->assertTrue(Event::hasListeners(OrderStatusUpdated::class));
        $this->assertTrue(Event::hasListeners(RefundRequested::class));
        $this->assertTrue(Event::hasListeners(RefundApproved::class));
        $this->assertTrue(Event::hasListeners(RefundRejected::class));
    }
}
