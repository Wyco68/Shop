<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Category;
use Illuminate\Http\Request;
use App\Support\StoreCache;
use Illuminate\Support\Facades\Cache;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $categories = Cache::remember(StoreCache::CATEGORIES_ACTIVE, 3600, function () {
            return Category::where('is_active', true)->get();
        });

        $filters = array_filter([
            'category' => $request->input('category'),
            'min_price' => $request->input('min_price'),
            'max_price' => $request->input('max_price'),
        ], fn ($v) => $v !== null && $v !== '');

        $page = max(1, (int) $request->input('page', 1));
        $cacheKey = StoreCache::productListingKey($filters, $page);
        StoreCache::registerProductListingKey($cacheKey);

        $products = Cache::remember($cacheKey, 600, function () use ($filters, $page) {
            $query = Product::with('category', 'discounts', 'defaultVariant.inventory')
                ->where('is_active', true);

            if (! empty($filters['category'])) {
                $query->where('category_id', (int) $filters['category']);
            }
            if (! empty($filters['min_price'])) {
                $query->where('base_price', '>=', (float) $filters['min_price']);
            }
            if (! empty($filters['max_price'])) {
                $query->where('base_price', '<=', (float) $filters['max_price']);
            }

            return $query->paginate(20, ['*'], 'page', $page)->withQueryString();
        });

        return view('products', compact('products', 'categories'));
    }

    public function show(Product $product)
    {
        if (!$product->is_active) {
            abort(404);
        }

        $product->load('category', 'discounts', 'variants.inventory');

        return view('product_detail', compact('product'));
    }
}