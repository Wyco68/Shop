<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminFeatureTest extends TestCase
{
    use RefreshDatabase;

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

    // 1. Access Control
    public function test_admin_can_access_admin_dashboard()
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin)->get('/admin')->assertOk();
    }

    public function test_user_cannot_access_admin_dashboard()
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get('/admin')->assertForbidden();
    }

    public function test_guest_cannot_access_admin_dashboard()
    {
        $this->get('/admin')->assertRedirect('/login');
    }

    public function test_admin_cannot_access_user_routes()
    {
        $admin = User::factory()->admin()->create();

        // Redirects to admin dashboard or home because of redirect_admin middleware
        $this->actingAs($admin)->get('/cart')->assertRedirect(route('admin.dashboard'));
        $this->actingAs($admin)->get('/orders/create')->assertRedirect(route('admin.dashboard'));
        $this->actingAs($admin)->get('/products')->assertRedirect(route('admin.dashboard'));
    }

    // 2. Orders Management
    public function test_admin_can_view_orders_list()
    {
        $admin = User::factory()->admin()->create();
        Order::factory()->count(3)->create();

        $this->actingAs($admin)->get(route('admin.orders.index'))
            ->assertOk()
            ->assertViewIs('admin.orders.index');
    }

    public function test_admin_can_view_order_detail()
    {
        $admin = User::factory()->admin()->create();
        $order = Order::factory()->create();

        $this->actingAs($admin)->get(route('admin.orders.show', $order))
            ->assertOk()
            ->assertViewIs('admin.orders.show');
    }

    public function test_admin_can_update_order_status()
    {
        $admin = User::factory()->admin()->create();
        $order = Order::factory()->create(['status' => Order::STATUS_PENDING]);

        $this->actingAs($admin)->patch(route('admin.orders.updateStatus', $order), [
            'status' => Order::STATUS_PAID,
        ])->assertRedirect();

        $this->assertEquals(Order::STATUS_PAID, $order->fresh()->status);
    }

    // 3. Payment Proof Display
    public function test_payment_proof_exists_visible_in_response()
    {
        $admin = User::factory()->admin()->create();
        $order = Order::factory()->create();
        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'proof_path' => 'payments/proof_123.jpg',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.orders.show', $order));
        
        $response->assertOk();
        $response->assertSee('payments/proof_123.jpg');
    }

    // 4. Inventory Behavior
    public function test_marking_order_as_paid_creates_out_movement_and_reduces_stock()
    {
        $admin = User::factory()->admin()->create();
        $data = $this->createProductWithStock([], 50);

        // Simulate reserved stock
        $data['inventory']->update(['reserved_quantity' => 5]);

        $order = Order::factory()->create(['status' => Order::STATUS_PENDING_PAYMENT]);
        
        OrderItem::factory()->create([
            'order_id' => $order->id,
            'variant_id' => $data['variant']->id,
            'quantity' => 5,
        ]);

        Payment::factory()->create([
            'order_id' => $order->id,
            'status' => 'pending'
        ]);

        $this->actingAs($admin)->post(route('admin.orders.processPayment', $order))
            ->assertRedirect();

        $data['inventory']->refresh();
        $this->assertEquals(45, $data['inventory']->stock_quantity);
        $this->assertEquals(0, $data['inventory']->reserved_quantity);

        $this->assertDatabaseHas('inventory_movements', [
            'variant_id' => $data['variant']->id,
            'type' => 'OUT',
            'quantity' => 5,
        ]);
    }

    // 5. Product Management
    public function test_admin_can_view_products()
    {
        $admin = User::factory()->admin()->create();
        Product::factory()->count(3)->create();

        $this->actingAs($admin)->get(route('admin.products.index'))
            ->assertOk()
            ->assertViewIs('admin.products.index');
    }

    public function test_admin_can_filter_products_by_category()
    {
        $admin = User::factory()->admin()->create();
        $category1 = Category::factory()->create();
        $category2 = Category::factory()->create();

        Product::factory()->create(['category_id' => $category1->id, 'name' => 'Cat1Product']);
        Product::factory()->create(['category_id' => $category2->id, 'name' => 'Cat2Product']);

        $this->actingAs($admin)->get(route('admin.products.index', ['category_id' => $category1->id]))
            ->assertOk()
            ->assertSee('Cat1Product')
            ->assertDontSee('Cat2Product');
    }

    public function test_admin_can_update_product()
    {
        $admin = User::factory()->admin()->create();
        $product = Product::factory()->create(['name' => 'Old Name', 'base_price' => 50.00]);

        $this->actingAs($admin)->put(route('admin.products.update', $product), [
            'name' => 'New Name',
            'slug' => 'new-name',
            'description' => $product->description,
            'category_id' => $product->category_id,
            'base_price' => 100.00,
            'is_active' => true,
        ])->assertRedirect();

        $this->assertEquals('New Name', $product->fresh()->name);
        $this->assertEquals(100.00, $product->fresh()->base_price);
    }
}
