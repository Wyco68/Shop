@extends('layouts.app')
@section('title', 'Checkout — Payment Instructions')
@section('content')

<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    <div class="flex items-center gap-2 text-sm text-gray-400 mb-6">
        <a href="{{ route('orders.create') }}" class="hover:text-gray-600">1. Payment method</a>
        <span>→</span>
        <span class="font-semibold text-gray-900">2. Instructions</span>
        <span>→</span>
        <span>3. Confirm order</span>
    </div>

    <h1 class="text-2xl font-bold text-gray-900 mb-2">Pay with {{ $paymentMethod->name }}</h1>
    <p class="text-sm text-gray-500 mb-8">Transfer the exact amount below, then place your order.</p>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white rounded-xl border border-gray-100 p-6">
                <h2 class="font-semibold text-gray-900 mb-4">Payment Instructions</h2>
                <div class="prose prose-sm max-w-none text-gray-700 whitespace-pre-line">{{ $paymentMethod->displayInstructions() }}</div>

                @if($paymentMethod->qrImageUrl())
                <div class="mt-6 flex flex-col items-center">
                    <p class="text-xs text-gray-500 mb-3">Scan to pay</p>
                    <img src="{{ $paymentMethod->qrImageUrl() }}" alt="{{ $paymentMethod->name }} QR code"
                         class="w-48 h-48 object-contain border border-gray-100 rounded-lg">
                </div>
                @endif

                <p class="mt-6 text-sm font-semibold text-gray-900">
                    Amount due: <span class="text-lg"><x-money :amount="$summary['total']" /></span>
                </p>
            </div>

            <form method="POST" action="{{ route('orders.store') }}" class="space-y-6">
                @csrf
                <input type="hidden" name="payment_method_id" value="{{ $paymentMethod->id }}">

                <div class="bg-white rounded-xl border border-gray-100 p-6">
                    <h2 class="font-semibold text-gray-900 mb-4">Order Notes</h2>
                    <textarea name="notes" rows="3" placeholder="Special instructions (optional)"
                        class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gray-400 resize-none">{{ old('notes') }}</textarea>
                    @error('notes') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <p class="text-xs text-gray-500">
                    By placing this order, you confirm that you will complete payment using the method above.
                    An admin will verify your payment and update the order status.
                </p>

                <button type="submit"
                    class="w-full bg-gray-900 text-white font-semibold py-3 rounded-xl hover:bg-gray-700 transition">
                    Place Order — <x-money :amount="$summary['total']" />
                </button>

                <a href="{{ route('orders.create') }}" class="block text-center text-sm text-gray-400 hover:text-gray-600">
                    ← Change payment method
                </a>
            </form>
        </div>

        <div class="lg:col-span-1">
            @include('orders.partials.summary-sidebar')
        </div>
    </div>
</div>

@endsection
