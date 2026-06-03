@extends('layouts.app')
@section('title', 'Cart')
@section('content')

<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    <h1 class="text-2xl font-bold text-gray-900 mb-8">Your Cart</h1>

    @if(isset($items) && count($items))

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

        {{-- Cart Items --}}
        <div class="lg:col-span-2 space-y-4">
            @foreach($items as $item)
            <div class="bg-white rounded-xl border border-gray-100 p-5 flex gap-4">

                {{-- Thumbnail --}}
                <div class="w-20 h-20 bg-gray-100 rounded-lg flex items-center justify-center shrink-0">
                    <svg class="w-10 h-10 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                </div>

                <div class="flex-1 min-w-0">
                    <a href="{{ route('products.show', $item['product']->slug) }}"
                       class="font-semibold text-gray-900 hover:text-gray-600 transition text-sm line-clamp-2">
                        {{ $item['product']->name }}
                    </a>
                    <p class="text-xs text-gray-400 mt-0.5">{{ $item['variant']->name ?? 'Default' }}</p>
                    <p class="text-sm text-gray-700 mt-1"><x-money :amount="$item['unit_price']" /> each</p>
                    @if($item['discount_info'])
                        <span class="text-xs text-red-500 font-medium">
                            − <x-money :amount="$item['discount_amount']" /> ({{ $item['discount_info']['name'] }})
                        </span>
                    @endif
                </div>

                <div class="flex flex-col items-end justify-between shrink-0">
                    <p class="font-bold text-gray-900"><x-money :amount="$item['final_line_total']" /></p>

                    {{-- Update Quantity --}}
                    <form action="{{ route('cart.update') }}" method="POST" class="flex items-center gap-2">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="variant_id" value="{{ $item['variant']->id }}">
                        <input type="number" name="quantity" value="{{ $item['quantity'] }}" min="1"
                            max="{{ $item['available_stock'] }}"
                            class="w-16 border border-gray-200 rounded-lg px-2 py-1 text-sm text-center focus:outline-none focus:ring-2 focus:ring-gray-400"
                            onchange="this.form.submit()">
                    </form>

                    {{-- Remove --}}
                    <form action="{{ route('cart.remove') }}" method="POST">
                        @csrf
                        @method('DELETE')
                        <input type="hidden" name="variant_id" value="{{ $item['variant']->id }}">
                        <button type="submit" class="text-xs text-red-400 hover:text-red-600 transition">Remove</button>
                    </form>
                </div>
            </div>
            @endforeach
        </div>

        {{-- Order Summary --}}
        <div class="lg:col-span-1">
            <div class="bg-white rounded-xl border border-gray-100 p-6 sticky top-24">
                <h2 class="font-semibold text-gray-900 mb-4">Order Summary</h2>
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between text-gray-600">
                        <span>Subtotal</span>
                        <span><x-money :amount="$subtotal" /></span>
                    </div>
                    @if($discount_total > 0)
                    <div class="flex justify-between text-red-500">
                        <span>Discount</span>
                        <span>− <x-money :amount="$discount_total" /></span>
                    </div>
                    @endif
                    <div class="border-t border-gray-100 pt-2 flex justify-between font-bold text-gray-900 text-base">
                        <span>Total</span>
                        <span><x-money :amount="$total" /></span>
                    </div>
                </div>

                <a href="{{ route('orders.create') }}"
                   class="block mt-6 w-full text-center bg-gray-900 text-white font-semibold py-3 rounded-xl hover:bg-gray-700 transition">
                    Proceed to Checkout
                </a>
                <a href="{{ route('products.index') }}"
                   class="block mt-3 w-full text-center border border-gray-200 text-gray-700 text-sm py-2 rounded-xl hover:bg-gray-50 transition">
                    Continue Shopping
                </a>
            </div>
        </div>
    </div>

    @else
    <div class="text-center py-20 bg-white rounded-xl border border-gray-100">
        <svg class="w-16 h-16 text-gray-200 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
        </svg>
        <h2 class="text-lg font-semibold text-gray-700 mb-2">Your cart is empty</h2>
        <p class="text-sm text-gray-400 mb-6">Add some products to get started.</p>
        <a href="{{ route('products.index') }}"
           class="inline-block bg-gray-900 text-white font-medium px-6 py-2.5 rounded-xl hover:bg-gray-700 transition">
            Browse Products
        </a>
    </div>
    @endif
</div>

@endsection