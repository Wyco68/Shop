<?php

namespace Tests\Feature;

use App\Events\RefundApproved;
use App\Events\RefundRejected;
use App\Events\RefundRequested;
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
use Tests\Concerns\BootstrapsStore;
use Tests\TestCase;

class RefundTest extends TestCase
{
    use BootstrapsStore;
    use RefreshDatabase;

    private function createDeliveredOrder(User $user): Order
    {
        $category = Category::factory()->create();
        $product  = Product::factory()->create(['category_id' => $category->id, 'base_price' => 50.00]);
        $variant  = ProductVariant::factory()->create(['product_id' => $product->id]);
        Inventory::factory()->create([
            'variant_id'       => $variant->id,
            'stock_quantity'   => 50,
            'reserved_quantity' => 0,
        ]);

        $cart = app(CartService::class)->getOrCreateCart($user);
        app(CartService::class)->addItem($cart, $variant->id, 1);

        $order = app(OrderService::class)->createFromCart($user, $cart, PaymentMethod::factory()->create()->id);
        $order->update(['status' => Order::STATUS_COMPLETED]);

        return $order->fresh();
    }

    public function test_user_can_request_refund_on_completed_order(): void
    {
        $this->bootstrapStore();

        $user  = User::factory()->create();
        $order = $this->createDeliveredOrder($user);

        $this->actingAs($user)
            ->post(route('orders.refund.store', $order), [
                'reason' => 'The product arrived damaged and is not functional.',
            ])
            ->assertRedirect(route('orders.show', $order));

        $this->assertDatabaseHas('refund_requests', [
            'order_id' => $order->id,
            'user_id'  => $user->id,
            'status'   => RefundRequest::STATUS_PENDING,
        ]);
    }

    public function test_user_cannot_request_refund_on_pending_order(): void
    {
        $this->bootstrapStore();

        $user     = User::factory()->create();
        $category = Category::factory()->create();
        $product  = Product::factory()->create(['category_id' => $category->id, 'base_price' => 50.00]);
        $variant  = ProductVariant::factory()->create(['product_id' => $product->id]);
        Inventory::factory()->create(['variant_id' => $variant->id, 'stock_quantity' => 50, 'reserved_quantity' => 0]);
        $cart = app(CartService::class)->getOrCreateCart($user);
        app(CartService::class)->addItem($cart, $variant->id, 1);
        $order = app(OrderService::class)->createFromCart($user, $cart, PaymentMethod::factory()->create()->id);

        // Order is in pending_payment — not refundable
        $this->actingAs($user)
            ->post(route('orders.refund.store', $order), [
                'reason' => 'Changed my mind.',
            ])
            ->assertRedirect(route('orders.show', $order));

        $this->assertDatabaseMissing('refund_requests', ['order_id' => $order->id]);
    }

    public function test_duplicate_refund_request_is_rejected(): void
    {
        $this->bootstrapStore();

        $user  = User::factory()->create();
        $order = $this->createDeliveredOrder($user);

        RefundRequest::create([
            'order_id' => $order->id,
            'user_id'  => $user->id,
            'reason'   => 'First request.',
            'status'   => RefundRequest::STATUS_PENDING,
        ]);

        $this->actingAs($user)
            ->post(route('orders.refund.store', $order), [
                'reason' => 'Second attempt.',
            ])
            ->assertRedirect(route('orders.show', $order))
            ->assertSessionHas('error');

        $this->assertCount(1, RefundRequest::where('order_id', $order->id)->get());
    }

    public function test_admin_can_approve_refund_request(): void
    {
        $admin  = User::factory()->admin()->create();
        $user   = User::factory()->create(['role' => 'user']);
        $order  = $this->createDeliveredOrder($user);

        $refund = RefundRequest::create([
            'order_id' => $order->id,
            'user_id'  => $user->id,
            'reason'   => 'Item broken.',
            'status'   => RefundRequest::STATUS_PENDING,
        ]);

        Event::fake([RefundApproved::class]);

        $this->actingAs($admin)
            ->post(route('admin.refunds.approve', $refund), [
                'admin_note' => 'Approved — issuing refund.',
            ])
            ->assertRedirect();

        $this->assertEquals(RefundRequest::STATUS_APPROVED, $refund->fresh()->status);
        $this->assertEquals(Order::STATUS_REFUNDED, $order->fresh()->status);

        Event::assertDispatched(RefundApproved::class);
    }

    public function test_admin_can_reject_refund_request(): void
    {
        $admin  = User::factory()->admin()->create();
        $user   = User::factory()->create(['role' => 'user']);
        $order  = $this->createDeliveredOrder($user);

        $refund = RefundRequest::create([
            'order_id' => $order->id,
            'user_id'  => $user->id,
            'reason'   => 'Item broken.',
            'status'   => RefundRequest::STATUS_PENDING,
        ]);

        Event::fake([RefundRejected::class]);

        $this->actingAs($admin)
            ->post(route('admin.refunds.reject', $refund), [
                'admin_note' => 'Cannot verify damage from photo.',
            ])
            ->assertRedirect();

        $this->assertEquals(RefundRequest::STATUS_REJECTED, $refund->fresh()->status);

        Event::assertDispatched(RefundRejected::class);
    }

    public function test_refund_request_dispatches_event(): void
    {
        $this->bootstrapStore();

        Event::fake([RefundRequested::class]);

        $user  = User::factory()->create();
        $order = $this->createDeliveredOrder($user);

        $this->actingAs($user)
            ->post(route('orders.refund.store', $order), [
                'reason' => 'The item stopped working after one day.',
            ]);

        Event::assertDispatched(RefundRequested::class);
    }
}
