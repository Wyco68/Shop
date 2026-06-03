@extends('layouts.admin')

@section('title', 'Edit Product')

@section('content')
<div class="space-y-8 max-w-5xl mx-auto">
    <!-- Top Action bar -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-white p-6 rounded-2xl border border-slate-100 shadow-sm shadow-slate-100/40">
        <div>
            <h1 class="text-2xl font-bold text-slate-800 tracking-tight">Edit Product</h1>
            <p class="text-sm text-slate-500 mt-1">Update details, images, categories, and availability status of a catalog item.</p>
        </div>
        <div class="shrink-0">
            <a href="{{ route('admin.products.index') }}" class="inline-flex items-center gap-2 px-4 py-2 border border-slate-200 text-slate-600 hover:text-slate-800 hover:bg-slate-50 rounded-xl font-medium text-sm transition">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Back to Catalog
            </a>
        </div>
    </div>

    <!-- Error Alerts -->
    @if($errors->any())
        <div class="bg-rose-50 border border-rose-200 text-rose-800 p-5 rounded-2xl shadow-lg shadow-rose-100/30 text-sm flex items-start gap-3">
            <div class="w-6 h-6 rounded-lg bg-rose-500 flex items-center justify-center text-white shrink-0 shadow-sm mt-0.5">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
            </div>
            <div>
                <p class="font-bold text-rose-950">Please review the following errors:</p>
                <ul class="list-disc pl-5 mt-1 space-y-0.5 text-rose-700/90 text-xs">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    <!-- Form layout -->
    <form action="{{ route('admin.products.update', $product) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            <!-- Left 2 Columns: Main Info -->
            <div class="lg:col-span-2 space-y-8">
                <!-- Product Information Card -->
                <x-admin.card title="Basic Information">
                    <div class="space-y-6">
                        <!-- Product Name -->
                        <div>
                            <label for="name" class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Product Name</label>
                            <input type="text" name="name" id="name" 
                                   class="w-full px-4 py-3 rounded-xl border border-slate-200 text-slate-800 text-sm font-medium focus:outline-none focus:border-sky-500 focus:ring-4 focus:ring-sky-500/10 placeholder-slate-400/70 transition shadow-inner" 
                                   placeholder="e.g. Brembo Front Brake Pad Set"
                                   value="{{ old('name', $product->name) }}" required>
                        </div>

                        <!-- Product Description -->
                        <div>
                            <label for="description" class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Description</label>
                            <textarea name="description" id="description" rows="6" 
                                      class="w-full px-4 py-3 rounded-xl border border-slate-200 text-slate-800 text-sm font-medium focus:outline-none focus:border-sky-500 focus:ring-4 focus:ring-sky-500/10 placeholder-slate-400/70 transition shadow-inner"
                                      placeholder="Provide a comprehensive description of the product..."
                                      required>{{ old('description', $product->description) }}</textarea>
                        </div>
                    </div>
                </x-admin.card>

                <!-- Inventory & Pricing Card -->
                <x-admin.card title="Pricing Detail">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                        <!-- Price -->
                        <div>
                            <label for="base_price" class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Base Price ($)</label>
                            <div class="relative rounded-xl shadow-inner">
                                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                    <span class="text-slate-400 text-sm font-bold">$</span>
                                </div>
                                <input type="number" step="0.01" min="0" name="base_price" id="base_price" 
                                       class="w-full pl-9 pr-4 py-3 rounded-xl border border-slate-200 text-slate-800 text-sm font-extrabold focus:outline-none focus:border-sky-500 focus:ring-4 focus:ring-sky-500/10 placeholder-slate-400 transition" 
                                       placeholder="0.00"
                                       value="{{ old('base_price', $product->base_price) }}" required>
                            </div>
                        </div>

                        <!-- Stock Status Helper -->
                        <div class="bg-slate-50 border border-slate-100 rounded-xl p-4 flex items-center justify-between">
                            <div>
                                <span class="block text-xxs font-bold text-slate-400 uppercase tracking-wider mb-0.5">Availability</span>
                                <span class="text-xs text-slate-500 font-medium">To modify exact variant stocks, edit individual inventory listings.</span>
                            </div>
                        </div>
                    </div>
                </x-admin.card>
            </div>

            <!-- Right Column: Status & Media -->
            <div class="space-y-8">
                
                <!-- Category Settings -->
                <x-admin.card title="Categorization">
                    <div>
                        <label for="category_id" class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Product Category</label>
                        <select name="category_id" id="category_id" 
                                class="w-full px-4 py-3 rounded-xl border border-slate-200 text-slate-700 text-sm font-semibold bg-white focus:outline-none focus:border-sky-500 focus:ring-4 focus:ring-sky-500/10 transition shadow-sm" required>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" {{ old('category_id', $product->category_id) == $category->id ? 'selected' : '' }}>
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </x-admin.card>

                <!-- Status Settings Toggle -->
                <x-admin.card title="Catalog Visibility">
                    <div>
                        <span class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-3">Product Status</span>
                        
                        <div class="flex items-center justify-between p-3.5 bg-slate-50 rounded-xl border border-slate-100">
                            <span class="text-sm font-semibold text-slate-700">Display in store</span>
                            
                            <!-- Toggle switch -->
                            <div class="flex items-center">
                                <input type="hidden" name="is_active" value="0">
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" id="is_active" name="is_active" value="1" {{ old('is_active', $product->is_active) ? 'checked' : '' }} class="sr-only peer">
                                    <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-sky-500/20 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-sky-500"></div>
                                </label>
                            </div>
                        </div>
                    </div>
                </x-admin.card>

                <x-admin.card title="Product Media">
                    <x-admin.media-upload
                        name="image"
                        id="product_image"
                        :preview="$product->image_path ? asset('storage/'.$product->image_path) : ''"
                        placeholder="Upload image cover"
                        button="Update File"
                        hint="Leave empty to keep current image"
                    />
                </x-admin.card>

                <!-- Actions -->
                <div class="bg-slate-900 rounded-2xl p-4 shadow-lg shadow-slate-950/20 border border-slate-850">
                    <button type="submit" class="w-full inline-flex justify-center items-center gap-2 py-3 px-4 border border-transparent shadow-sm text-sm font-semibold rounded-xl text-white bg-sky-500 hover:bg-sky-600 focus:outline-none focus:ring-4 focus:ring-sky-500/20 transition duration-150">
                        <svg class="w-4.5 h-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Update Product
                    </button>
                </div>
            </div>

        </div>
    </form>
</div>
@endsection
