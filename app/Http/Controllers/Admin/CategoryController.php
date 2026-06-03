<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use App\Support\StoreCache;

class CategoryController extends Controller
{
    use AuthorizesRequests;

    public function index()
    {
        $this->authorize('viewAny', Category::class);

        $categories = Category::withCount('products')->get();
        return view('admin.categories.index', compact('categories'));
    }

    public function store(Request $request)
    {
        $this->authorize('create', Category::class);

        $request->validate(['name' => 'required|string|max:255']);

        Category::create($request->only('name'));

        StoreCache::forgetCategories();

        return redirect()->route('admin.categories.index')->with('success', 'Category created.');
    }

    public function update(Request $request, Category $category)
    {
        $this->authorize('update', $category);

        $request->validate([
            'name' => 'required|string|max:255',
            'is_active' => 'boolean',
        ]);

        $category->update($request->only('name', 'is_active'));

        StoreCache::forgetCategories();

        return redirect()->route('admin.categories.index')->with('success', 'Category updated.');
    }

    public function destroy(Category $category)
    {
        $this->authorize('delete', $category);

        $category->delete();

        StoreCache::forgetCategories();

        return redirect()->route('admin.categories.index')->with('success', 'Category deleted.');
    }
}
