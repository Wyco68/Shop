<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Seeder;

/**
 * Lightweight seed for Render / portfolio demos (~12 products, seconds to run).
 * Full catalog: php artisan db:seed (uses CatalogSeeder — large, local only).
 */
class RenderDemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            BrandSeeder::class,
            CategorySeeder::class,
            PaymentMethodSeeder::class,
            UserSeeder::class,
        ]);

        $brakeCategory = Category::where('slug', 'brake-pads')->first()
            ?? Category::where('slug', 'brake-system')->first();
        $filterCategory = Category::where('slug', 'oil-filters')->first()
            ?? Category::where('slug', 'engine-system')->first();
        $bosch = Brand::where('slug', 'bosch')->first();
        $toyota = Brand::where('slug', 'toyota')->first();

        $samples = [
            [
                'name' => 'Ceramic Brake Pad Set — Front',
                'slug' => 'ceramic-brake-pad-front',
                'description' => 'Low-dust ceramic pads for daily driving. Demo listing.',
                'category_id' => $brakeCategory?->id,
                'brand_id' => $bosch?->id,
                'base_price' => 89.99,
                'sku' => 'DEMO-BRK-001',
                'stock' => 40,
            ],
            [
                'name' => 'Premium Oil Filter',
                'slug' => 'premium-oil-filter-demo',
                'description' => 'High-efficiency oil filter. Compatible with most 4-cylinder engines.',
                'category_id' => $filterCategory?->id,
                'brand_id' => $bosch?->id,
                'base_price' => 24.50,
                'sku' => 'DEMO-FLT-001',
                'stock' => 120,
            ],
            [
                'name' => 'OEM Windshield Wiper Blade 24"',
                'slug' => 'wiper-blade-24-demo',
                'description' => 'All-weather wiper blade for portfolio demo.',
                'category_id' => $filterCategory?->id,
                'brand_id' => $toyota?->id,
                'base_price' => 18.00,
                'sku' => 'DEMO-WIP-024',
                'stock' => 75,
            ],
            [
                'name' => 'Iridium Spark Plug (Single)',
                'slug' => 'iridium-spark-plug-demo',
                'description' => 'Long-life iridium plug — demo SKU.',
                'category_id' => $filterCategory?->id,
                'brand_id' => $bosch?->id,
                'base_price' => 12.99,
                'sku' => 'DEMO-SPK-001',
                'stock' => 200,
            ],
            [
                'name' => 'Heavy-Duty Air Filter',
                'slug' => 'heavy-duty-air-filter-demo',
                'description' => 'Panel air filter for compact SUVs.',
                'category_id' => $filterCategory?->id,
                'brand_id' => $toyota?->id,
                'base_price' => 32.00,
                'sku' => 'DEMO-AIR-001',
                'stock' => 60,
            ],
            [
                'name' => 'Brake Rotor — Front Pair',
                'slug' => 'brake-rotor-front-demo',
                'description' => 'Ventilated rotors, coated for corrosion resistance.',
                'category_id' => $brakeCategory?->id,
                'brand_id' => $bosch?->id,
                'base_price' => 145.00,
                'sku' => 'DEMO-ROT-001',
                'stock' => 25,
            ],
        ];

        foreach ($samples as $row) {
            $product = Product::updateOrCreate(
                ['slug' => $row['slug']],
                [
                    'name' => $row['name'],
                    'description' => $row['description'],
                    'category_id' => $row['category_id'],
                    'brand_id' => $row['brand_id'],
                    'base_price' => $row['base_price'],
                    'images' => [],
                    'compatibility' => ['Toyota Corolla 2015-2020', 'Honda Civic 2016-2021'],
                    'is_active' => true,
                ]
            );

            $variant = ProductVariant::updateOrCreate(
                ['sku' => $row['sku']],
                [
                    'product_id' => $product->id,
                    'name' => 'Standard',
                    'price_override' => null,
                    'is_active' => true,
                ]
            );

            Inventory::updateOrCreate(
                ['variant_id' => $variant->id],
                [
                    'stock_quantity' => $row['stock'],
                    'reserved_quantity' => 0,
                    'low_stock_threshold' => 5,
                ]
            );
        }
    }
}
