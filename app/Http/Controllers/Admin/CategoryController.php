<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Services\SecureUploadService;
use App\Support\StoreCache;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly SecureUploadService $uploads,
    ) {}

    public function index()
    {
        $this->authorize('viewAny', Category::class);

        $categories = Category::withCount('products')->get();

        return view('admin.categories.index', compact('categories'));
    }

    public function store(Request $request)
    {
        $this->authorize('create', Category::class);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'icon' => ['nullable', 'file', 'max:'.SecureUploadService::MAX_CATEGORY_ICON_KB],
        ]);

        $data = ['name' => $validated['name'], 'is_active' => $request->boolean('is_active', true)];

        if ($request->hasFile('icon')) {
            try {
                $data['icon_path'] = $this->uploads->storeImage(
                    $request->file('icon'),
                    'categories',
                    SecureUploadService::categoryIconMimes(),
                    SecureUploadService::MAX_CATEGORY_ICON_KB,
                );
            } catch (\InvalidArgumentException $e) {
                return back()->withInput()->with('error', $e->getMessage());
            }
        }

        Category::create($data);

        StoreCache::forgetCategories();

        return redirect()->route('admin.categories.index')->with('success', 'Category created.');
    }

    public function update(Request $request, Category $category)
    {
        $this->authorize('update', $category);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'is_active' => 'boolean',
            'icon' => ['nullable', 'file', 'max:'.SecureUploadService::MAX_CATEGORY_ICON_KB],
        ]);

        $data = [
            'name' => $validated['name'],
            'is_active' => $request->boolean('is_active'),
        ];

        if ($request->hasFile('icon')) {
            try {
                $this->uploads->deleteIfExists($category->icon_path);
                $data['icon_path'] = $this->uploads->storeImage(
                    $request->file('icon'),
                    'categories',
                    SecureUploadService::categoryIconMimes(),
                    SecureUploadService::MAX_CATEGORY_ICON_KB,
                );
            } catch (\InvalidArgumentException $e) {
                return back()->withInput()->with('error', $e->getMessage());
            }
        }

        $category->update($data);

        StoreCache::forgetCategories();

        return redirect()->route('admin.categories.index')->with('success', 'Category updated.');
    }

    public function destroy(Category $category)
    {
        $this->authorize('delete', $category);

        $this->uploads->deleteIfExists($category->icon_path);
        $category->delete();

        StoreCache::forgetCategories();

        return redirect()->route('admin.categories.index')->with('success', 'Category deleted.');
    }
}
