<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use App\Support\StoreCache;
use Tests\TestCase;

class ProductCatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_products_index_loads_with_active_products(): void
    {
        $category = Category::factory()->create(['name' => 'Electronics']);
        $product = Product::factory()->create([
            'name' => 'Phone Holder',
            'base_price' => 10.00,
            'category_id' => $category->id,
            'is_active' => true,
        ]);

        $response = $this->get('/products');

        $response->assertStatus(200);
        $response->assertSee('Phone Holder');
        $response->assertSee('10.00');
    }

    public function test_inactive_products_hidden_from_index(): void
    {
        $category = Category::factory()->create();
        Product::factory()->create([
            'name' => 'Hidden Product',
            'category_id' => $category->id,
            'is_active' => false,
        ]);

        $response = $this->get('/products');

        $response->assertStatus(200);
        $response->assertDontSee('Hidden Product');
    }

    public function test_product_show_page_loads_by_slug(): void
    {
        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'name' => 'USB Charger',
            'slug' => 'usb-charger',
            'category_id' => $category->id,
            'is_active' => true,
        ]);
        ProductVariant::factory()->create(['product_id' => $product->id]);

        $response = $this->get('/products/usb-charger');

        $response->assertStatus(200);
        $response->assertSee('USB Charger');
    }

    public function test_product_show_displays_uploaded_image_url(): void
    {
        Storage::fake('public');

        $path = 'products/test-cover.jpg';
        Storage::disk('public')->put($path, 'fake-image');

        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'slug' => 'imaged-widget',
            'category_id' => $category->id,
            'is_active' => true,
            'images' => [$path],
        ]);
        ProductVariant::factory()->create(['product_id' => $product->id]);

        $expectedUrl = Storage::disk('public')->url($path);

        $this->get('/products/imaged-widget')
            ->assertOk()
            ->assertSee($expectedUrl, false);
    }

    public function test_inactive_product_returns_404(): void
    {
        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'slug' => 'inactive-item',
            'category_id' => $category->id,
            'is_active' => false,
        ]);

        $response = $this->get('/products/inactive-item');

        $response->assertStatus(404);
    }

    public function test_category_filter_cache_invalidates_when_product_image_updated(): void
    {
        Storage::fake('public');

        $path = 'products/filtered-cover.jpg';
        Storage::disk('public')->put($path, 'fake-image');

        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'name' => 'Filtered Widget',
            'category_id' => $category->id,
            'is_active' => true,
            'images' => null,
        ]);

        $this->get('/products?category='.$category->id)->assertOk();

        $product->update(['images' => [$path]]);
        StoreCache::forgetProducts();

        $expectedUrl = Storage::disk('public')->url($path);

        $this->get('/products?category='.$category->id)
            ->assertOk()
            ->assertSee($expectedUrl, false);
    }

    public function test_products_filter_by_category(): void
    {
        $cat1 = Category::factory()->create(['name' => 'Safety']);
        $cat2 = Category::factory()->create(['name' => 'Cleaning']);
        Product::factory()->create(['name' => 'Fire Ext', 'category_id' => $cat1->id]);
        Product::factory()->create(['name' => 'Sponge', 'category_id' => $cat2->id]);

        $response = $this->get('/products?category=' . $cat1->id);

        $response->assertStatus(200);
        $response->assertSee('Fire Ext');
        $response->assertDontSee('Sponge');
    }

    public function test_products_filter_by_price_range(): void
    {
        $category = Category::factory()->create();
        Product::factory()->create(['name' => 'Cheap', 'base_price' => 5.00, 'category_id' => $category->id]);
        Product::factory()->create(['name' => 'Expensive', 'base_price' => 50.00, 'category_id' => $category->id]);

        $response = $this->get('/products?min_price=10&max_price=100');

        $response->assertStatus(200);
        $response->assertDontSee('Cheap');
        $response->assertSee('Expensive');
    }
}