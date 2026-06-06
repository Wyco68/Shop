<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Services\CartService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BootstrapsStore;
use Tests\TestCase;

class CartTest extends TestCase
{
    use BootstrapsStore;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->bootstrapStore();
    }

    private function createVariantWithStock(int $stock = 50): ProductVariant
    {
        $category = Category::factory()->create();
        $product = Product::factory()->create(['category_id' => $category->id]);
        $variant = ProductVariant::factory()->create(['product_id' => $product->id]);
        Inventory::factory()->create(['variant_id' => $variant->id, 'stock_quantity' => $stock]);
        return $variant;
    }

    public function test_add_item_to_cart(): void
    {
        $user = User::factory()->create();
        $variant = $this->createVariantWithStock();

        $cartService = app(CartService::class);
        $cart = $cartService->getOrCreateCart($user);
        $cartService->addItem($cart, $variant->id, 3);

        $this->assertDatabaseHas('cart_items', [
            'cart_id' => $cart->id,
            'variant_id' => $variant->id,
            'quantity' => 3,
        ]);
    }

    public function test_add_same_item_increments_quantity(): void
    {
        $user = User::factory()->create();
        $variant = $this->createVariantWithStock();

        $cartService = app(CartService::class);
        $cart = $cartService->getOrCreateCart($user);
        $cartService->addItem($cart, $variant->id, 2);
        $cartService->addItem($cart, $variant->id, 3);

        $this->assertDatabaseHas('cart_items', [
            'cart_id' => $cart->id,
            'variant_id' => $variant->id,
            'quantity' => 5,
        ]);
    }

    public function test_update_cart_item_quantity(): void
    {
        $user = User::factory()->create();
        $variant = $this->createVariantWithStock();

        $cartService = app(CartService::class);
        $cart = $cartService->getOrCreateCart($user);
        $cartService->addItem($cart, $variant->id, 2);
        $cartService->updateQuantity($cart, $variant->id, 7);

        $this->assertDatabaseHas('cart_items', [
            'cart_id' => $cart->id,
            'variant_id' => $variant->id,
            'quantity' => 7,
        ]);
    }

    public function test_remove_item_from_cart(): void
    {
        $user = User::factory()->create();
        $variant = $this->createVariantWithStock();

        $cartService = app(CartService::class);
        $cart = $cartService->getOrCreateCart($user);
        $cartService->addItem($cart, $variant->id, 2);
        $cartService->removeItem($cart, $variant->id);

        $this->assertDatabaseMissing('cart_items', [
            'cart_id' => $cart->id,
            'variant_id' => $variant->id,
        ]);
    }

    public function test_clear_cart_removes_all_items(): void
    {
        $user = User::factory()->create();
        $v1 = $this->createVariantWithStock();
        $v2 = $this->createVariantWithStock();

        $cartService = app(CartService::class);
        $cart = $cartService->getOrCreateCart($user);
        $cartService->addItem($cart, $v1->id, 1);
        $cartService->addItem($cart, $v2->id, 2);
        $cartService->clearCart($cart);

        $this->assertEquals(0, $cart->items()->count());
    }

    public function test_cart_summary_calculates_totals(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'base_price' => 25.00,
        ]);
        $variant = ProductVariant::factory()->create(['product_id' => $product->id]);
        Inventory::factory()->create(['variant_id' => $variant->id, 'stock_quantity' => 100]);

        $cartService = app(CartService::class);
        $cart = $cartService->getOrCreateCart($user);
        $cartService->addItem($cart, $variant->id, 4);

        $summary = $cartService->getCartSummary($cart);

        $this->assertEquals(100.00, $summary['subtotal']);
        $this->assertEquals(100.00, $summary['total']);
        $this->assertCount(1, $summary['items']);
    }

    public function test_guest_cannot_access_cart_page(): void
    {
        $this->get('/cart')->assertRedirect('/login');
    }

    public function test_cannot_add_inactive_product_to_cart(): void
    {
        $user = User::factory()->create();
        $variant = $this->createVariantWithStock();
        $variant->product->update(['is_active' => false]);

        $cartService = app(CartService::class);
        $cart = $cartService->getOrCreateCart($user);

        $this->expectException(\RuntimeException::class);
        $cartService->addItem($cart, $variant->id, 1);
    }

    public function test_cannot_add_inactive_variant_to_cart(): void
    {
        $user = User::factory()->create();
        $variant = $this->createVariantWithStock();
        $variant->update(['is_active' => false]);

        $cartService = app(CartService::class);
        $cart = $cartService->getOrCreateCart($user);

        $this->expectException(\RuntimeException::class);
        $cartService->addItem($cart, $variant->id, 1);
    }

    public function test_cannot_add_soft_deleted_product_to_cart(): void
    {
        $user = User::factory()->create();
        $variant = $this->createVariantWithStock();
        $variant->product->delete();

        $cartService = app(CartService::class);
        $cart = $cartService->getOrCreateCart($user);

        $this->expectException(\RuntimeException::class);
        $cartService->addItem($cart, $variant->id, 1);
    }

    public function test_cart_summary_removes_unavailable_items(): void
    {
        $user = User::factory()->create();
        $available = $this->createVariantWithStock();
        $unavailable = $this->createVariantWithStock();

        $cartService = app(CartService::class);
        $cart = $cartService->getOrCreateCart($user);
        $cartService->addItem($cart, $available->id, 2);
        $cartService->addItem($cart, $unavailable->id, 1);

        $unavailable->product->update(['is_active' => false]);

        $summary = $cartService->getCartSummary($cart);

        $this->assertCount(1, $summary['items']);
        $this->assertEquals($available->id, $summary['items'][0]['variant']->id);
        $this->assertDatabaseMissing('cart_items', [
            'cart_id' => $cart->id,
            'variant_id' => $unavailable->id,
        ]);
    }

    public function test_cart_quantity_is_capped_to_available_stock(): void
    {
        $user = User::factory()->create();
        $variant = $this->createVariantWithStock(3);

        $cartService = app(CartService::class);
        $cart = $cartService->getOrCreateCart($user);
        $cartService->addItem($cart, $variant->id, 10);

        $this->assertDatabaseHas('cart_items', [
            'cart_id' => $cart->id,
            'variant_id' => $variant->id,
            'quantity' => 3,
        ]);
    }

    public function test_cart_quantity_cannot_exceed_max_line_quantity(): void
    {
        $user = User::factory()->create();
        $variant = $this->createVariantWithStock(200);

        $cartService = app(CartService::class);
        $cart = $cartService->getOrCreateCart($user);
        $cartService->addItem($cart, $variant->id, 150);

        $this->assertDatabaseHas('cart_items', [
            'cart_id' => $cart->id,
            'variant_id' => $variant->id,
            'quantity' => CartService::MAX_LINE_QUANTITY,
        ]);
    }

    public function test_checkout_rejects_cart_with_deactivated_product(): void
    {
        $user = User::factory()->create();
        $variant = $this->createVariantWithStock();

        $cartService = app(CartService::class);
        $cart = $cartService->getOrCreateCart($user);
        $cartService->addItem($cart, $variant->id, 1);

        $variant->product->update(['is_active' => false]);

        $this->expectException(\RuntimeException::class);
        $cartService->validateCartForCheckout($cart);
    }
}
