@extends('layouts.app')
@section('title', 'Submit Payment — Order #' . $order->id)
@section('content')

<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    <div class="mb-8">
        <a href="{{ route('orders.show', $order) }}" class="text-sm text-gray-400 hover:text-gray-600">← Order #{{ $order->id }}</a>
        <h1 class="text-2xl font-bold text-gray-900 mt-2">Complete Your Payment</h1>
        <p class="text-sm text-gray-500 mt-1">
            Confirm your contact details and upload proof so we can verify your <x-money :amount="$order->total" /> payment.
        </p>
    </div>

    @if($order->paymentMethod)
    <div class="bg-white rounded-xl border border-gray-100 p-5 mb-6 text-sm">
        <p class="font-semibold text-gray-900">{{ $order->paymentMethod->name }}</p>
        @if($order->paymentMethod->instructions)
        <p class="text-gray-600 mt-2 whitespace-pre-line">{{ $order->paymentMethod->instructions }}</p>
        @endif
        @if($order->paymentMethod->qrImageUrl())
        <div class="mt-4 flex justify-center">
            <img src="{{ $order->paymentMethod->qrImageUrl() }}" alt="Payment QR"
                 class="w-36 h-36 object-contain border border-gray-100 rounded-lg">
        </div>
        @endif
    </div>
    @endif

    @if($order->latestPayment?->proof_path)
    <div class="bg-green-50 border border-green-200 rounded-xl p-5 mb-6 text-sm text-green-800">
        Payment proof already submitted. <a href="{{ route('orders.show', $order) }}" class="underline font-medium">View order status</a>
    </div>
    @else
    <form method="POST" action="{{ route('orders.payment.submit', $order) }}" enctype="multipart/form-data" class="space-y-6">
        @csrf

        <div class="bg-white rounded-xl border border-gray-100 p-6">
            <h2 class="font-semibold text-gray-900 mb-1">Contact &amp; Shipping Details</h2>
            <p class="text-xs text-gray-500 mb-4">Please verify your phone number and address before submitting payment proof.</p>

            <div class="space-y-4">
                <div>
                    <label for="phone_num" class="block text-sm font-medium text-gray-700 mb-1">Phone number <span class="text-red-500">*</span></label>
                    <input type="text" id="phone_num" name="phone_num"
                        value="{{ old('phone_num', $user->phone_num) }}"
                        required
                        placeholder="e.g. 09XX XXX XXXX"
                        class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gray-400 @error('phone_num') border-red-400 @enderror">
                    @error('phone_num') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="address" class="block text-sm font-medium text-gray-700 mb-1">Shipping address <span class="text-red-500">*</span></label>
                    <textarea id="address" name="address" rows="3" required
                        placeholder="Street, city, postal code"
                        class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gray-400 resize-none @error('address') border-red-400 @enderror">{{ old('address', $user->address) }}</textarea>
                    @error('address') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-gray-100 p-6">
            <h2 class="font-semibold text-gray-900 mb-1">Payment Proof</h2>
            <p class="text-xs text-gray-500 mb-4">Upload a screenshot or photo of your transfer receipt (JPG, PNG, up to 5MB).</p>

            <label class="flex flex-col items-center justify-center border-2 border-dashed border-gray-200 rounded-xl px-6 py-8 cursor-pointer hover:border-gray-400 transition" for="payment_proof">
                <svg class="w-8 h-8 text-gray-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
                <span class="text-sm text-gray-500">Click to upload payment proof</span>
            </label>
            <input type="file" id="payment_proof" name="payment_proof" accept="image/*" required class="sr-only"
                onchange="document.getElementById('file-name').textContent = this.files[0]?.name || ''">
            <p id="file-name" class="text-xs text-gray-500 mt-2 text-center"></p>
            @error('payment_proof') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
        </div>

        <button type="submit"
            class="w-full bg-gray-900 text-white font-semibold py-3 rounded-xl hover:bg-gray-700 transition">
            Submit Payment Proof
        </button>
    </form>
    @endif
</div>

@endsection
