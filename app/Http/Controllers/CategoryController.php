<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Support\StoreCache;
use Illuminate\Support\Facades\Cache;

class CategoryController extends Controller
{
    public function index()
    {
        $categories = Cache::remember(StoreCache::CATEGORIES_INDEX, 3600, function () {
            return Category::where('is_active', true)->withCount('products')->get();
        });

        return view('categories', compact('categories'));
    }
}
