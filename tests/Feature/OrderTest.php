<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Discount;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Services\CartService;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderTest extends TestCase
{
    use RefreshDatabase;

    private function paymentMethodId(): int
    {
        return PaymentMethod::factory()->create()->id;
    }

    private function createProductWithStock(array $overrides = [], int $stock = 50): array
    {
        $category = Category::factory()->create();
        $product = Product::factory()->create(array_merge([
            'category_id' => $category->id,
            'base_price' => 10.00,
        ], $overrides));
        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'name' => 'Default',
        ]);
        $inventory = Inventory::factory()->create([
            'variant_id' => $variant->id,
            'stock_quantity' => $stock,
            'reserved_quantity' => 0,
        ]);

        return compact('category', 'product', 'variant', 'inventory');
    }

    public function test_authenticated_user_can_create_order_from_cart(): void
    {
        $user = User::factory()->create();
        $data = $this->createProductWithStock(['base_price' => 15.00], 100);

        $cartService = app(CartService::class);
        $cart = $cartService->getOrCreateCart($user);
        $cartService->addItem($cart, $data['variant']->id, 3);

        $orderService = app(OrderService::class);
        $order = $orderService->createFromCart($user, $cart, $this->paymentMethodId());

        $this->assertEquals('pending_payment', $order->status);
        $this->assertEquals(45.00, (float) $order->total);
        $this->assertCount(1, $order->orderItems);
        $this->assertEquals(3, $order->orderItems->first()->quantity);
    }

    public function test_order_creation_reserves_stock(): void
    {
        $user = User::factory()->create();
        $data = $this->createProductWithStock([], 50);

        $cartService = app(CartService::class);
        $cart = $cartService->getOrCreateCart($user);
        $cartService->addItem($cart, $data['variant']->id, 5);

        $orderService = app(OrderService::class);
        $orderService->createFromCart($user, $cart, $this->paymentMethodId());

        $data['inventory']->refresh();
        $this->assertEquals(5, $data['inventory']->reserved_quantity);
        $this->assertEquals(50, $data['inventory']->stock_quantity);
    }

    public function test_order_creation_fails_with_insufficient_stock(): void
    {
        $user = User::factory()->create();
        $data = $this->createProductWithStock([], 5);

        $cartService = app(CartService::class);
        $cart = $cartService->getOrCreateCart($user);
        $cartService->addItem($cart, $data['variant']->id, 5);

        // addItem() caps the line at available stock, so the shortfall has to
        // appear between add-to-cart and checkout (e.g. another customer's order).
        $data['inventory']->update(['stock_quantity' => 2]);

        $this->expectException(\RuntimeException::class);

        $orderService = app(OrderService::class);
        $orderService->createFromCart($user, $cart, $this->paymentMethodId());
    }

    public function test_order_creation_clears_cart(): void
    {
        $user = User::factory()->create();
        $data = $this->createProductWithStock([], 50);

        $cartService = app(CartService::class);
        $cart = $cartService->getOrCreateCart($user);
        $cartService->addItem($cart, $data['variant']->id, 2);

        $orderService = app(OrderService::class);
        $orderService->createFromCart($user, $cart, $this->paymentMethodId());

        $this->assertEquals(0, $cart->fresh()->items()->count());
    }

    public function test_order_cancellation_releases_stock(): void
    {
        $user = User::factory()->create();
        $data = $this->createProductWithStock([], 50);

        $cartService = app(CartService::class);
        $cart = $cartService->getOrCreateCart($user);
        $cartService->addItem($cart, $data['variant']->id, 5);

        $orderService = app(OrderService::class);
        $order = $orderService->createFromCart($user, $cart, $this->paymentMethodId());

        $this->assertEquals(5, $data['inventory']->fresh()->reserved_quantity);

        $orderService->cancelOrder($order);

        $this->assertEquals('cancelled', $order->fresh()->status);
        $this->assertEquals(0, $data['inventory']->fresh()->reserved_quantity);
    }

    public function test_orders_index_requires_authentication(): void
    {
        $this->get('/orders')->assertRedirect('/login');
    }

    public function test_discount_applied_to_order(): void
    {
        $user = User::factory()->create();
        $data = $this->createProductWithStock(['base_price' => 20.00], 100);

        Discount::factory()->create([
            'product_id' => $data['product']->id,
            'type' => 'percentage',
            'value' => 10.00,
            'min_quantity' => 1,
            'is_active' => true,
        ]);

        $cartService = app(CartService::class);
        $cart = $cartService->getOrCreateCart($user);
        $cartService->addItem($cart, $data['variant']->id, 2);

        $orderService = app(OrderService::class);
        $order = $orderService->createFromCart($user, $cart, $this->paymentMethodId());

        // 2 x $20 = $40 subtotal, 10% off = $4 discount
        $this->assertEquals(40.00, (float) $order->subtotal);
        $this->assertEquals(4.00, (float) $order->discount_total);
        $this->assertEquals(36.00, (float) $order->total);
    }

    public function test_no_discount_when_below_min_quantity(): void
    {
        $user = User::factory()->create();
        $data = $this->createProductWithStock(['base_price' => 10.00], 100);

        Discount::factory()->create([
            'product_id' => $data['product']->id,
            'type' => 'percentage',
            'value' => 20.00,
            'min_quantity' => 5,
            'is_active' => true,
        ]);

        $cartService = app(CartService::class);
        $cart = $cartService->getOrCreateCart($user);
        $cartService->addItem($cart, $data['variant']->id, 2);

        $orderService = app(OrderService::class);
        $order = $orderService->createFromCart($user, $cart, $this->paymentMethodId());

        $this->assertEquals(20.00, (float) $order->subtotal);
        $this->assertEquals(0.00, (float) $order->discount_total);
        $this->assertEquals(20.00, (float) $order->total);
    }
}
