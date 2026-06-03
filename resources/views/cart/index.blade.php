@extends('layouts.app')
@section('title', 'Shopping Cart')
@section('content')

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <h1 class="text-3xl font-bold tracking-tight text-gray-900 mb-8">Your Cart</h1>

    @if(isset($items) && count($items))
        <div class="flex flex-col lg:flex-row gap-12">
            
            {{-- Cart Items --}}
            <div class="flex-1 space-y-6">
                @foreach($items as $item)
                    <div class="flex items-center gap-6 bg-white p-6 rounded-2xl shadow-sm border border-gray-100/50 transition hover:shadow-md">
                        <div class="w-24 h-24 shrink-0 bg-gray-50 rounded-xl flex items-center justify-center">
                            <svg class="w-10 h-10 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        
                        <div class="flex-1 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                            <div>
                                <a href="{{ route('products.show', $item['product']->slug ?? $item['product']->id) }}" class="text-lg font-semibold text-gray-900 hover:text-gray-600 transition">
                                    {{ $item['product']->name }}
                                </a>
                                @if(isset($item['variant']) && $item['variant']->name)
                                    <p class="text-sm text-gray-500 mt-1">Variant: {{ $item['variant']->name }}</p>
                                @endif
                                <p class="text-gray-500 text-sm mt-1">Qty: <span class="font-medium text-gray-900">{{ $item['quantity'] }}</span></p>
                            </div>
                            
                            <div class="text-right">
                                @if($item['quantity'] > 1)
                                    <p class="text-xs text-gray-400 mb-1 font-medium tracking-wide"><x-money :amount="($item['final_line_total'] / $item['quantity'])" /> each</p>
                                @endif
                                <p class="text-xl font-bold text-gray-900"><x-money :amount="$item['final_line_total']" /></p>
                                @if($item['discount_amount'] > 0)
                                    <p class="text-sm text-red-500 font-medium">Saved <x-money :amount="$item['discount_amount'] * $item['quantity']" /></p>
                                @endif
                            </div>
                        </div>
                        
                        {{-- Future implementation: Remove button here --}}
                        <button class="p-2 text-gray-400 hover:text-red-500 transition-colors ml-2" title="Remove Item">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                @endforeach
            </div>

            {{-- Order Summary --}}
            <div class="w-full lg:w-96 shrink-0">
                <div class="bg-white rounded-2xl p-8 shadow-sm border border-gray-100/50 sticky top-24">
                    <h2 class="text-lg font-semibold text-gray-900 mb-6">Order Summary</h2>
                    
                    <div class="space-y-4 text-sm text-gray-600 border-b border-gray-100 pb-6 mb-6">
                        <div class="flex justify-between">
                            <span>Subtotal</span>
                            <span class="font-medium text-gray-900"><x-money :amount="$subtotal" /></span>
                        </div>
                        @if($discount_total > 0)
                        <div class="flex justify-between text-red-500">
                            <span>Discount</span>
                            <span class="font-medium">-<x-money :amount="$discount_total" /></span>
                        </div>
                        @endif
                        <div class="flex justify-between">
                            <span>Shipping</span>
                            <span class="font-medium text-gray-900">Calculated at checkout</span>
                        </div>
                    </div>
                    
                    <div class="flex justify-between items-center mb-8">
                        <span class="text-base font-semibold text-gray-900">Total</span>
                        <span class="text-2xl font-bold text-gray-900"><x-money :amount="$total" /></span>
                    </div>
                    
                    <form action="{{ route('checkout.process') }}" method="POST">
                        @csrf
                        <button type="submit" class="w-full bg-black text-white py-4 rounded-xl font-semibold hover:bg-gray-800 transition-colors shadow-sm text-center">
                            Proceed to Checkout
                        </button>
                    </form>
                    <div class="mt-4 text-center">
                        <a href="{{ route('products.index') }}" class="text-sm text-gray-500 hover:text-gray-900 transition">Continue Shopping</a>
                    </div>
                </div>
            </div>
            
        </div>
    @else
        <div class="text-center py-24 bg-white rounded-3xl border border-gray-100/50 shadow-sm mt-8">
            <svg class="w-20 h-20 text-gray-200 mx-auto mb-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
            </svg>
            <h2 class="text-2xl font-semibold text-gray-900 mb-2">Your cart is empty</h2>
            <p class="text-gray-500 mb-8 max-w-md mx-auto">Looks like you haven't added any car parts to your cart yet. Browse our catalog to find what you need.</p>
            <a href="{{ route('products.index') }}" class="inline-block bg-black text-white font-semibold px-8 py-3.5 rounded-xl hover:bg-gray-800 transition shadow-sm">
                Start Shopping
            </a>
        </div>
    @endif
</div>

@endsection