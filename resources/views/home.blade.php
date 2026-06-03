@extends('layouts.app')
@section('title', 'Home')
@section('content')

{{-- Hero --}}
<section class="bg-[#fbfbfd] text-gray-900 py-24 border-b border-gray-100">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h1 class="text-5xl sm:text-7xl font-bold tracking-tight mb-6">
            Welcome to {{ config('shop.name') }}.
        </h1>
        <p class="text-xl text-gray-500 mb-10 max-w-2xl mx-auto font-medium">
            Quality products. Trusted brands. Fast shipping. Everything you need in one place.
        </p>
        <a href="{{ route('products.index') }}"
           class="inline-block bg-black text-white font-semibold px-8 py-4 rounded-full hover:scale-105 transition-transform duration-300 shadow-md">
            Start Shopping
        </a>
    </div>
</section>

{{-- Categories --}}
@if($categories->isNotEmpty())
<section class="py-20 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="text-3xl font-bold text-gray-900 mb-12 text-center tracking-tight">Browse by Category</h2>
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4">
            @foreach($categories as $category)
            <a href="{{ route('products.index', ['category' => $category->id]) }}"
               class="flex flex-col items-center p-6 bg-white rounded-3xl hover:shadow-xl hover:-translate-y-1 transition-all duration-300 border border-gray-100/50 group">
                <div class="w-16 h-16 bg-[#f5f5f7] group-hover:bg-black group-hover:scale-110 rounded-full flex items-center justify-center mb-4 transition-all duration-300">
                    <x-category-icon :name="$category->name" class="w-8 h-8 text-gray-600 group-hover:text-white transition-colors duration-300" />
                </div>
                <span class="text-sm font-medium text-gray-700 text-center">{{ $category->name }}</span>
            </a>
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- Featured / Discounted Products --}}
@if($discountedProducts->isNotEmpty())
<section class="py-20 bg-[#fbfbfd]">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="text-3xl font-bold text-gray-900 mb-12 text-center tracking-tight">Featured Deals</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            @foreach($discountedProducts as $product)
            <a href="{{ route('products.show', $product->slug) }}"
               class="bg-white rounded-3xl border border-gray-100/50 hover:shadow-xl hover:-translate-y-1 transition-all duration-300 group overflow-hidden relative flex flex-col">
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
                        <span class="text-xl font-bold tracking-tight text-gray-900"><x-money :amount="$product->base_price" /></span>
                    </div>
                </div>
            </a>
            @endforeach
        </div>
        <div class="mt-12 text-center">
            <a href="{{ route('products.index') }}" class="inline-block border border-gray-300 text-gray-900 px-8 py-3 rounded-full hover:border-black transition font-semibold text-sm">
                View All Products
            </a>
        </div>
    </div>
</section>
@endif

{{-- CTA --}}
@guest
{{-- CTA --}}
<section class="py-24 bg-white border-t border-gray-100">
    <div class="max-w-4xl mx-auto px-4 text-center">
        <h2 class="text-4xl font-bold tracking-tight text-gray-900 mb-4">Ready to get started?</h2>
        <p class="text-lg text-gray-500 mb-10">Create an account to track orders, manage your cart, and access exclusive deals.</p>
        <div class="flex flex-col sm:flex-row gap-4 justify-center">
            <a href="{{ route('register') }}" class="bg-black text-white font-semibold px-8 py-4 rounded-full hover:scale-105 transition-transform shadow-md">Create Account</a>
            <a href="{{ route('login') }}" class="bg-gray-100 text-gray-900 font-semibold px-8 py-4 rounded-full hover:bg-gray-200 transition-colors">Sign In</a>
        </div>
    </div>
</section>
@endguest

@endsection