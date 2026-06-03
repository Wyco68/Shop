@extends('layouts.admin')

@section('title', 'Products & Inventory')

@section('content')
<div class="space-y-8">
    
    <!-- Top Action bar -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 bg-white p-6 rounded-2xl border border-slate-100 shadow-sm shadow-slate-100/40">
        <div>
            <h1 class="text-2xl font-bold text-slate-800 tracking-tight">Products & Inventory</h1>
            <p class="text-sm text-slate-500 mt-1">Manage product catalog, details, categories, and real-time stock levels.</p>
        </div>
        <div class="flex flex-wrap items-center gap-3 shrink-0">
            <p class="text-xs font-semibold text-slate-400 bg-slate-50 border border-slate-100 px-3 py-2 rounded-xl">
                {{ $products->total() }} product{{ $products->total() !== 1 ? 's' : '' }} Total
            </p>
            <a href="{{ route('admin.categories.index') }}" class="inline-flex items-center gap-2 px-4 py-2 border border-slate-200 text-slate-600 hover:text-slate-800 hover:bg-slate-50 rounded-xl font-medium text-sm transition">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M7 7h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                Categories
            </a>
            <a href="{{ route('admin.products.create') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-sky-500 hover:bg-sky-600 text-white rounded-xl font-semibold text-sm shadow-sm transition">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                </svg>
                Add Product
            </a>
        </div>
    </div>

    <!-- Responsive Layout wrapper -->
    <div class="flex flex-col lg:flex-row gap-8">
        
        <!-- Sidebar Filters (Vertical on Desktop, Horizontal Scroll on Mobile) -->
        <aside class="w-full lg:w-60 shrink-0">
            <form method="GET" action="{{ route('admin.products.index') }}" id="filter-form">
                <!-- Mobile Horizontal Chips -->
                <div class="lg:hidden">
                    <span class="text-xxs font-bold text-slate-400 uppercase tracking-widest block mb-2">Category Filters</span>
                    <div class="flex gap-2 overflow-x-auto pb-3 -mx-6 px-6 scrollbar-none">
                        <button type="submit" name="category_id" value="" 
                                class="shrink-0 px-4 py-1.5 rounded-full text-xs font-semibold border transition {{ !request('category_id') ? 'bg-slate-800 border-slate-800 text-white' : 'bg-white border-slate-200 text-slate-600' }}">
                            All
                        </button>
                        @foreach($categories as $cat)
                            <button type="submit" name="category_id" value="{{ $cat->id }}" 
                                    class="shrink-0 px-4 py-1.5 rounded-full text-xs font-semibold border transition {{ request('category_id') == $cat->id ? 'bg-slate-800 border-slate-800 text-white' : 'bg-white border-slate-200 text-slate-600' }}">
                                {{ $cat->name }}
                            </button>
                        @endforeach
                    </div>
                </div>

                <!-- Desktop Sidebar Card -->
                <div class="hidden lg:block bg-white rounded-2xl border border-slate-100 shadow-sm shadow-slate-100/40 p-6 space-y-6 sticky top-24">
                    <div>
                        <span class="text-xxs font-bold text-slate-400 uppercase tracking-widest block mb-4">Categories</span>
                        <div class="space-y-2">
                            <label class="flex items-center gap-3 text-sm cursor-pointer group">
                                <input type="radio" name="category_id" value="" {{ !request('category_id') ? 'checked' : '' }}
                                    class="w-4 h-4 text-sky-500 border-slate-200 focus:ring-sky-500/20 focus:ring-offset-0 transition" onchange="this.form.submit()">
                                <span class="text-slate-600 group-hover:text-slate-800 transition font-medium">All Categories</span>
                            </label>
                            @foreach($categories as $cat)
                                <label class="flex items-center gap-3 text-sm cursor-pointer group">
                                    <input type="radio" name="category_id" value="{{ $cat->id }}"
                                        {{ request('category_id') == $cat->id ? 'checked' : '' }}
                                        class="w-4 h-4 text-sky-500 border-slate-200 focus:ring-sky-500/20 focus:ring-offset-0 transition" onchange="this.form.submit()">
                                    <span class="text-slate-600 group-hover:text-slate-800 transition font-medium">{{ $cat->name }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    @if(request()->has('category_id') && request('category_id') != '')
                        <div class="pt-4 text-center border-t border-slate-100">
                            <a href="{{ route('admin.products.index') }}"
                               class="inline-flex items-center gap-1.5 text-xs font-semibold text-rose-500 hover:text-rose-600 transition">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                                Clear filters
                            </a>
                        </div>
                    @endif
                </div>
            </form>
        </aside>

        <!-- Product Grid Area -->
        <div class="flex-1 min-w-0">
            @if($products->isEmpty())
                <div class="text-center py-20 bg-white rounded-2xl border border-slate-100 shadow-sm shadow-slate-100/40">
                    <svg class="w-12 h-12 text-slate-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <p class="text-slate-500 font-semibold text-base">No products found.</p>
                    <p class="text-slate-400 text-xs mt-1">Try resetting your filters or categories selection.</p>
                    <a href="{{ route('admin.products.index') }}" class="mt-4 inline-flex items-center gap-1.5 px-4 py-2 border border-slate-200 text-slate-600 hover:text-slate-800 rounded-xl text-xs font-semibold transition">
                        Reset Filters
                    </a>
                </div>
            @else
                <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-5">
                    @foreach($products as $product)
                    @php
                        $totalStock = 0;
                        $totalReserved = 0;
                        foreach($product->variants as $variant) {
                            if($variant->inventory) {
                                $totalStock += $variant->inventory->stock_quantity;
                                $totalReserved += $variant->inventory->reserved_quantity;
                            }
                        }
                        $availableStock = max(0, $totalStock - $totalReserved);
                        $stockPercent = $totalStock > 0 ? min(100, ($availableStock / $totalStock) * 100) : 0;
                        
                        if ($availableStock > 10) {
                            $stockStatus = 'in_stock';
                        } elseif ($availableStock > 0) {
                            $stockStatus = 'low_stock';
                        } else {
                            $stockStatus = 'out_of_stock';
                        }
                    @endphp
                    <a href="{{ route('admin.products.edit', $product) }}" class="group block bg-white rounded-3xl border border-slate-100 shadow-sm shadow-slate-100/40 hover:-translate-y-1 hover:shadow-xl transition-all duration-300 overflow-hidden flex flex-col relative">
                        <!-- Product Image Cover -->
                        <div class="bg-[#f5f5f7] h-56 flex items-center justify-center p-6 relative overflow-hidden">
                            @if($product->image_path)
                                <img src="{{ asset('storage/'.$product->image_path) }}" alt="" class="h-full w-full object-cover rounded-xl group-hover:scale-105 transition-transform duration-500">
                            @else
                                <svg class="w-20 h-20 text-slate-300 group-hover:scale-105 transition-transform duration-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                            @endif
                            
                            <!-- Badges -->
                            <div class="absolute top-4 left-4 flex flex-col gap-1.5">
                                @if(!$product->is_active)
                                    <span class="bg-rose-500 text-white text-xxs px-2.5 py-1 rounded-full font-bold tracking-wider border border-rose-600/10 shadow-sm shadow-rose-100">
                                        INACTIVE
                                    </span>
                                @endif
                                <x-admin.status-badge :status="$stockStatus" />
                            </div>
                        </div>

                        <!-- Product Content Details -->
                        <div class="p-5 flex-1 flex flex-col bg-white">
                            <div class="flex justify-between items-start mb-2 gap-4">
                                <span class="text-xxs font-bold tracking-wider text-slate-400 uppercase truncate">{{ $product->category?->name ?? 'Uncategorized' }}</span>
                                <span class="text-base font-extrabold tracking-tight text-slate-800 shrink-0"><x-money :amount="$product->base_price" /></span>
                            </div>
                            <h3 class="font-bold text-slate-800 text-sm leading-snug group-hover:text-sky-500 transition-colors line-clamp-2 mb-4">
                                {{ $product->name }}
                            </h3>
                            
                            <div class="mt-auto space-y-3">
                                <!-- Inventory Details Container -->
                                <div class="bg-slate-50/50 rounded-xl p-3 border border-slate-100">
                                    <div class="grid grid-cols-3 text-xxs font-bold text-slate-500 uppercase text-center mb-2 divide-x divide-slate-100">
                                        <div>
                                            <div class="text-slate-400 font-semibold mb-0.5">Avail</div>
                                            <div class="text-slate-700 text-xs font-extrabold">{{ $availableStock }}</div>
                                        </div>
                                        <div>
                                            <div class="text-slate-400 font-semibold mb-0.5">Resv</div>
                                            <div class="text-amber-600 text-xs font-extrabold">{{ $totalReserved }}</div>
                                        </div>
                                        <div>
                                            <div class="text-slate-400 font-semibold mb-0.5">Total</div>
                                            <div class="text-slate-700 text-xs font-extrabold">{{ $totalStock }}</div>
                                        </div>
                                    </div>
                                    
                                    <!-- Progress Bar Indicators -->
                                    <div class="w-full bg-slate-200 rounded-full h-1.5 overflow-hidden flex">
                                        @if($stockStatus == 'in_stock')
                                            <div class="bg-emerald-500 h-1.5 rounded-full" style="width: {{ $stockPercent }}%"></div>
                                        @elseif($stockStatus == 'low_stock')
                                            <div class="bg-amber-500 h-1.5 rounded-full" style="width: {{ $stockPercent }}%"></div>
                                        @else
                                            <div class="bg-rose-500 h-1.5 rounded-full" style="width: 100%"></div>
                                        @endif

                                        @if($totalReserved > 0 && $totalStock > 0)
                                            <div class="bg-yellow-400 h-1.5" style="width: {{ ($totalReserved / $totalStock) * 100 }}%"></div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </a>
                    @endforeach
                </div>

                <!-- Custom Pagination -->
                <div class="mt-8 bg-white border border-slate-100 p-4 rounded-2xl flex flex-col sm:flex-row items-center justify-between gap-4 shadow-sm shadow-slate-100/40">
                    @if($products->hasPages())
                        <div class="text-xs text-slate-500 font-semibold uppercase tracking-wider">
                            Page {{ $products->currentPage() }} of {{ $products->lastPage() }}
                        </div>
                    @endif
                    <div class="w-full sm:w-auto">
                        {{ $products->links() }}
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection