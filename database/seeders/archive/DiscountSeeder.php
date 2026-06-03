<?php

namespace Database\Seeders;

use App\Models\Discount;
use App\Models\Product;
use Illuminate\Database\Seeder;

class DiscountSeeder extends Seeder
{
    public function run(): void
    {
        $product = Product::where('slug', 'seat-cushion')->first();
        if ($product) {
            Discount::updateOrCreate(
                ['name' => 'Seat Cushion 10% Off'],
                [
                    'product_id' => $product->id,
                    'type' => 'percentage',
                    'value' => 10.00,
                    'min_quantity' => 1,
                    'is_active' => true,
                ]
            );
        }

        $product2 = Product::where('slug', 'microfiber-cloth')->first();
        if ($product2) {
            Discount::updateOrCreate(
                ['name' => 'Microfiber Cloth Bulk Discount'],
                [
                    'product_id' => $product2->id,
                    'type' => 'fixed',
                    'value' => 0.50,
                    'min_quantity' => 5,
                    'is_active' => true,
                ]
            );
        }
    }
}