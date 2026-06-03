@extends('layouts.app')
@section('title', 'Products')
@section('content')

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-10 gap-4">
        <h1 class="text-4xl font-bold tracking-tight text-gray-900">All Products</h1>
        <p class="text-sm font-medium text-gray-500">{{ $products->total() }} product{{ $products->total() !== 1 ? 's' : '' }} found</p>
    </div>

    <div class="flex flex-col lg:flex-row gap-8">

        {{-- Sidebar Filters --}}
        <aside class="lg:w-64 shrink-0">
            <form method="GET" action="{{ route('products.index') }}" id="filter-form">
                <div class="bg-white rounded-2xl border border-gray-100/50 shadow-sm p-6 space-y-8 sticky top-24">
                    <div>
                        <h2 class="text-sm font-bold tracking-wider text-gray-900 uppercase mb-4">Category</h2>
                        <div class="space-y-3">
                            <label class="flex items-center gap-3 text-sm cursor-pointer group">
                                <input type="radio" name="category" value="" {{ !request('category') ? 'checked' : '' }}
                                    class="w-4 h-4 text-black border-gray-300 focus:ring-black transition" onchange="this.form.submit()">
                                <span class="text-gray-600 group-hover:text-black transition-colors font-medium">All Categories</span>
                            </label>
                            @foreach($categories as $cat)
                            <label class="flex items-center gap-3 text-sm cursor-pointer group">
                                <input type="radio" name="category" value="{{ $cat->id }}"
                                    {{ request('category') == $cat->id ? 'checked' : '' }}
                                    class="w-4 h-4 text-black border-gray-300 focus:ring-black transition" onchange="this.form.submit()">
                                <span class="text-gray-600 group-hover:text-black transition-colors font-medium">{{ $cat->name }}</span>
                            </label>
                            @endforeach
                        </div>
                    </div>

                    <div class="pt-6 border-t border-gray-100">
                        <h2 class="text-sm font-bold tracking-wider text-gray-900 uppercase mb-4">Price Range</h2>
                        <div class="space-y-4">
                            <div>
                                <label class="text-xs font-semibold text-gray-500 block mb-1.5 uppercase tracking-wide">Min Price</label>
                                <input type="number" name="min_price" value="{{ request('min_price') }}" placeholder="$0"
                                    class="w-full border border-gray-200/80 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-black focus:ring-1 focus:ring-black transition">
                            </div>
                            <div>
                                <label class="text-xs font-semibold text-gray-500 block mb-1.5 uppercase tracking-wide">Max Price</label>
                                <input type="number" name="max_price" value="{{ request('max_price') }}" placeholder="Any"
                                    class="w-full border border-gray-200/80 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-black focus:ring-1 focus:ring-black transition">
                            </div>
                            <button type="submit"
                                class="w-full mt-2 bg-black text-white text-sm font-medium rounded-xl px-4 py-3 hover:bg-gray-800 transition-colors shadow-sm">
                                Apply Filter
                            </button>
                        </div>
                    </div>

                    @if(request()->hasAny(['category', 'min_price', 'max_price']))
                    <div class="pt-4 text-center">
                        <a href="{{ route('products.index') }}"
                           class="inline-block text-xs font-medium text-gray-500 hover:text-black transition-colors border-b border-gray-300 hover:border-black">
                            Clear all filters
                        </a>
                    </div>
                    @endif
                </div>
            </form>
        </aside>

        {{-- Product Grid --}}
        <div class="flex-1">
            @if($products->isEmpty())
                <div class="text-center py-20 bg-white rounded-xl border border-gray-100">
                    <svg class="w-12 h-12 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <p class="text-gray-500 font-medium">No products found.</p>
                    <a href="{{ route('products.index') }}" class="mt-3 inline-block text-sm text-gray-700 underline">Clear filters</a>
                </div>
            @else
                <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-5">
                    @foreach($products as $product)
                    <a href="{{ route('products.show', $product->slug) }}"
                       class="bg-white rounded-3xl border border-gray-100/50 hover:shadow-xl hover:-translate-y-1 transition-all duration-300 group overflow-hidden flex flex-col relative">
                        <div class="bg-[#f5f5f7] h-56 flex items-center justify-center p-6 relative">
                            <svg class="w-20 h-20 text-gray-300 group-hover:scale-105 transition-transform duration-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            @php $activeDiscount = $product->discounts->first(); @endphp
                            @if($activeDiscount)
                                <span class="absolute top-4 left-4 bg-black text-white text-xs px-2.5 py-1 rounded-full font-bold tracking-wide">
                                    {{ $activeDiscount->type === 'percentage' ? $activeDiscount->value.'% OFF' : '$'.$activeDiscount->value.' OFF' }}
                                </span>
                            @endif
                        </div>
                        <div class="p-5 flex-1 flex flex-col bg-white">
                            <p class="text-xs font-semibold tracking-wider text-gray-400 uppercase mb-2">{{ $product->category?->name }}</p>
                            <h3 class="font-bold text-gray-900 text-base leading-snug group-hover:text-blue-600 transition-colors line-clamp-2 flex-1">
                                {{ $product->name }}
                            </h3>
                            <div class="mt-4 flex items-end justify-between">
                                <div class="flex flex-col">
                                    <span class="text-xl font-bold tracking-tight text-gray-900"><x-money :amount="$product->base_price" /></span>
                                </div>
                                @php $stock = $product->defaultVariant?->available_stock ?? 0; @endphp
                                @if($stock > 0)
                                    <span class="text-xs bg-green-50 text-green-700 px-2 py-1 rounded-md font-medium border border-green-200">In stock</span>
                                @else
                                    <span class="text-xs bg-red-50 text-red-700 px-2 py-1 rounded-md font-medium border border-red-200">Out of stock</span>
                                @endif
                            </div>
                        </div>
                    </a>
                    @endforeach
                </div>

                <div class="mt-8">
                    @if($products->hasPages())
                        <div class="mb-4 text-sm text-gray-500 font-medium text-center">
                            Page {{ $products->currentPage() }} of {{ $products->lastPage() }}
                        </div>
                    @endif
                    {{ $products->links() }}
                </div>
            @endif
        </div>
    </div>
</div>

@endsection