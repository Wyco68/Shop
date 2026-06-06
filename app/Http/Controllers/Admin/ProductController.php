<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Category;
use App\Models\ProductVariant;
use App\Models\Inventory;
use App\Services\SecureUploadService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use App\Support\StoreCache;

class ProductController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly SecureUploadService $uploads,
    ) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', Product::class);

        $query = Product::with('category', 'variants.inventory')->withTrashed();

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        $products = $query->paginate(20)->withQueryString();
        $categories = Category::all();

        return view('admin.products.index', compact('products', 'categories'));
    }

    public function create()
    {
        $this->authorize('create', Product::class);

        $categories = Category::where('is_active', true)->get();
        return view('admin.products.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $this->authorize('create', Product::class);

        $validated = $request->validate([
            'name'          => 'required|string|max:255',
            'description'   => 'required|string',
            'base_price'    => 'required|numeric|min:0',
            'category_id'   => 'required|exists:categories,id',
            'image'         => ['nullable', 'file', 'max:'.SecureUploadService::MAX_PRODUCT_IMAGE_KB],
            'sku'           => 'required|string|unique:product_variants,sku',
            'initial_stock' => 'required|integer|min:0',
        ]);

        $data = $request->only('name', 'description', 'base_price', 'category_id');
        if ($request->hasFile('image')) {
            try {
                $path = $this->uploads->storeImage(
                    $request->file('image'),
                    'products',
                    SecureUploadService::productImageMimes(),
                    SecureUploadService::MAX_PRODUCT_IMAGE_KB,
                );
            } catch (\InvalidArgumentException $e) {
                return back()->withInput()->with('error', $e->getMessage());
            }
            $data['images'] = [$path];
        }

        $product = Product::create($data);

        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'sku'        => $validated['sku'],
            'name'       => 'Default',
        ]);

        Inventory::create([
            'variant_id'     => $variant->id,
            'stock_quantity' => $validated['initial_stock'],
        ]);

        StoreCache::forgetProducts();
        StoreCache::forgetCategories();

        return redirect()->route('admin.products.index')->with('success', 'Product created.');
    }

    public function edit(Product $product)
    {
        $this->authorize('update', $product);

        $product->load('variants.inventory');
        $categories = Category::where('is_active', true)->get();

        return view('admin.products.edit', compact('product', 'categories'));
    }

    public function update(Request $request, Product $product)
    {
        $this->authorize('update', $product);

        $request->validate([
            'name'        => 'required|string|max:255',
            'description' => 'required|string',
            'base_price'  => 'required|numeric|min:0',
            'category_id' => 'required|exists:categories,id',
            'image'       => ['nullable', 'file', 'max:'.SecureUploadService::MAX_PRODUCT_IMAGE_KB],
            'is_active'   => 'boolean',
        ]);

        $data = $request->only('name', 'description', 'base_price', 'category_id', 'is_active');
        if ($request->hasFile('image')) {
            try {
                if ($product->image_path) {
                    $this->uploads->deleteIfExists($product->image_path);
                }
                $path = $this->uploads->storeImage(
                    $request->file('image'),
                    'products',
                    SecureUploadService::productImageMimes(),
                    SecureUploadService::MAX_PRODUCT_IMAGE_KB,
                );
            } catch (\InvalidArgumentException $e) {
                return back()->withInput()->with('error', $e->getMessage());
            }
            $data['images'] = [$path];
        }

        $product->update($data);

        StoreCache::forgetProducts();

        return redirect()->route('admin.products.index')->with('success', 'Product updated.');
    }

    public function destroy(Product $product)
    {
        $this->authorize('delete', $product);

        $product->delete(); // soft delete

        StoreCache::forgetProducts();

        return redirect()->route('admin.products.index')->with('success', 'Product archived.');
    }
}
