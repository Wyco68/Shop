@extends('layouts.app')
@section('title', $product->name)
@section('content')

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">

    {{-- Breadcrumb --}}
    <nav class="text-sm text-gray-500 mb-6 flex items-center gap-2">
        <a href="{{ route('home') }}" class="hover:text-gray-900 transition">Home</a>
        <span>/</span>
        <a href="{{ route('products.index') }}" class="hover:text-gray-900 transition">Products</a>
        <span>/</span>
        <span class="text-gray-700 font-medium">{{ $product->name }}</span>
    </nav>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-16 lg:gap-24">

        {{-- Product Image --}}
        <div class="bg-[#f5f5f7] rounded-[2rem] flex items-center justify-center h-[30rem] lg:h-[40rem] p-12 relative overflow-hidden">
            <x-product-image
                :url="$product->image_url"
                :alt="$product->name"
                class="{{ $product->image_url ? 'h-full w-full max-h-full object-contain' : 'w-48 h-48 text-gray-300' }}"
            />
        </div>

        {{-- Product Info --}}
        <div class="flex flex-col justify-center">
            @if($product->category)
                <span class="text-xs font-bold tracking-widest text-gray-400 uppercase mb-4">{{ $product->category->name }}</span>
            @endif

            <h1 class="text-4xl sm:text-5xl font-bold tracking-tight text-gray-900 mb-6">{{ $product->name }}</h1>

            {{-- Price & Discount --}}
            <div class="flex items-center gap-4 mb-8">
                <span class="text-3xl font-bold text-gray-900"><x-money :amount="$product->base_price" /></span>
                @php $activeDiscount = $product->discounts->first(); @endphp
                @if($activeDiscount)
                    <span class="bg-black text-white text-sm px-3 py-1 rounded-full font-bold tracking-wide">
                        {{ $activeDiscount->badgeLabel() }}
                    </span>
                @endif
            </div>

            <p class="text-lg text-gray-600 leading-relaxed mb-10">{{ $product->description }}</p>

            {{-- Add to Cart Form --}}
            <div class="bg-gray-50/50 p-8 rounded-3xl border border-gray-100/80">
                @if($product->variants->isNotEmpty())
                    @auth
                    <form action="{{ route('cart.add') }}" method="POST" class="space-y-6">
                        @csrf

                        {{-- Variant select --}}
                        @if($product->variants->count() > 1)
                        <div>
                            <label for="variant_id" class="block text-sm font-semibold text-gray-900 mb-2">Variant</label>
                            <select name="variant_id" id="variant_id"
                                class="w-full bg-white border border-gray-200 rounded-xl px-4 py-3.5 text-base focus:outline-none focus:border-black focus:ring-1 focus:ring-black transition shadow-sm appearance-none cursor-pointer">
                                @foreach($product->variants as $variant)
                                    @if($variant->available_stock > 0)
                                    <option value="{{ $variant->id }}">
                                        {{ $variant->name ?? 'Default' }}
                                        @if($variant->price_override) — <x-money :amount="$variant->price_override" /> @endif
                                        ({{ $variant->available_stock }} in stock)
                                    </option>
                                    @endif
                                @endforeach
                            </select>
                        </div>
                        @else
                            <input type="hidden" name="variant_id" value="{{ $product->variants->first()->id }}">
                            @php $defaultVariant = $product->variants->first(); @endphp
                            <p class="text-sm text-gray-500 mb-4">
                                Status:
                                @if($defaultVariant->available_stock > 0)
                                    <span class="text-green-600 font-semibold">{{ $defaultVariant->available_stock }} available in stock</span>
                                @else
                                    <span class="text-red-500 font-semibold">Out of stock</span>
                                @endif
                            </p>
                        @endif

                        <div>
                            <label for="quantity" class="block text-sm font-semibold text-gray-900 mb-2">Quantity</label>
                            <input type="number" name="quantity" id="quantity" value="1" min="1"
                                class="w-32 bg-white border border-gray-200 rounded-xl px-4 py-3.5 text-base focus:outline-none focus:border-black focus:ring-1 focus:ring-black transition shadow-sm">
                        </div>

                        @php
                            $totalStock = $product->variants->sum('available_stock');
                        @endphp
                        <button type="submit"
                            @if($totalStock === 0) disabled @endif
                            class="w-full bg-black text-white font-semibold py-4 rounded-xl hover:bg-gray-800 transition shadow-sm mt-4 text-lg disabled:opacity-50 disabled:cursor-not-allowed">
                            @if($totalStock === 0) Out of Stock @else Add to Cart @endif
                        </button>
                    </form>
                    @else
                    <div class="space-y-4">
                        <a href="{{ route('login') }}"
                           class="block text-center w-full bg-black text-white font-semibold py-4 rounded-xl hover:bg-gray-800 transition shadow-sm">
                            Log in to Add to Cart
                        </a>
                        <p class="text-sm text-gray-500 text-center">or <a href="{{ route('register') }}" class="text-black font-semibold hover:underline">create an account</a></p>
                    </div>
                    @endauth
                @else
                    <p class="text-gray-500 italic text-center py-4">No variants available for this product.</p>
                @endif
            </div>
        </div>
    </div>
</div>

@endsection