@extends('layouts.app')
@section('title', 'Checkout — Payment Method')
@section('content')

<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    <div class="flex items-center gap-2 text-sm text-gray-400 mb-6">
        <span class="font-semibold text-gray-900">1. Payment method</span>
        <span>→</span>
        <span>2. Instructions</span>
        <span>→</span>
        <span>3. Confirm order</span>
    </div>

    <h1 class="text-2xl font-bold text-gray-900 mb-8">Select Payment Method</h1>

    @if(isset($summary) && count($summary['items']))

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <div class="lg:col-span-2">
            <form method="POST" action="{{ route('orders.checkout.method') }}" class="space-y-4">
                @csrf

                @forelse($paymentMethods as $method)
                <label class="flex items-start gap-4 bg-white rounded-xl border border-gray-100 p-5 cursor-pointer hover:border-gray-300 transition has-[:checked]:border-gray-900 has-[:checked]:ring-1 has-[:checked]:ring-gray-900">
                    <input type="radio" name="payment_method_id" value="{{ $method->id }}"
                        class="mt-1 text-gray-900 focus:ring-gray-900"
                        {{ old('payment_method_id') == $method->id ? 'checked' : '' }}
                        required>
                    <div>
                        <p class="font-semibold text-gray-900">{{ $method->name }}</p>
                        @if($method->instructions)
                        <p class="text-xs text-gray-500 mt-1 line-clamp-2">{{ Str::limit($method->instructions, 120) }}</p>
                        @endif
                    </div>
                </label>
                @empty
                <p class="text-gray-500 text-sm">No payment methods are available. Please contact support.</p>
                @endforelse

                @error('payment_method_id')
                <p class="text-red-500 text-xs">{{ $message }}</p>
                @enderror

                @if($paymentMethods->isNotEmpty())
                <button type="submit"
                    class="w-full bg-gray-900 text-white font-semibold py-3 rounded-xl hover:bg-gray-700 transition">
                    Continue to Payment Instructions
                </button>
                @endif
            </form>
        </div>

        <div class="lg:col-span-1 space-y-6">
            @include('orders.partials.summary-sidebar')
            @if(isset($supportContacts) && $supportContacts->isNotEmpty())
                <div class="bg-white rounded-xl border border-gray-100 p-5">
                    <h2 class="text-sm font-semibold text-gray-900 mb-3">Need help?</h2>
                    <x-support-contacts :contacts="$supportContacts" compact />
                </div>
            @endif
        </div>
    </div>

    @else
    <div class="text-center py-20 bg-white rounded-xl border border-gray-100">
        <h2 class="text-lg font-semibold text-gray-700 mb-2">Your cart is empty</h2>
        <a href="{{ route('products.index') }}" class="inline-block mt-4 bg-gray-900 text-white px-6 py-2.5 rounded-xl hover:bg-gray-700 transition font-medium">
            Browse Products
        </a>
    </div>
    @endif
</div>

@endsection
