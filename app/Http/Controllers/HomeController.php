<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Support\StoreCache;
use Illuminate\Support\Facades\Cache;

class HomeController extends Controller
{
    public function index()
    {
        $categories = Cache::remember(StoreCache::CATEGORIES_ACTIVE, 3600, function () {
            return Category::where('is_active', true)->get();
        });

        $discountedProducts = Cache::remember(StoreCache::HOME_FEATURED, 600, function () {
            return Product::with('category', 'discounts', 'defaultVariant.inventory')
                ->where('is_active', true)
                ->whereHas('discounts', function ($q) {
                    $q->active();
                })
                ->take(4)
                ->get();
        });

        return view('home', compact('categories', 'discountedProducts'));
    }
}