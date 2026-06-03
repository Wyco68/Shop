@extends('layouts.app')
@section('title', 'Order #' . $order->id)
@section('content')

@php
    $statusColors = [
        'pending'          => 'bg-yellow-100 text-yellow-700',
        'pending_payment'  => 'bg-amber-100 text-amber-800',
        'paid'             => 'bg-blue-100 text-blue-700',
        'processing'       => 'bg-purple-100 text-purple-700',
        'shipped'          => 'bg-indigo-100 text-indigo-700',
        'completed'        => 'bg-green-100 text-green-700',
        'cancelled'        => 'bg-red-100 text-red-700',
        'refunded'         => 'bg-gray-100 text-gray-600',
    ];
    $badge = $statusColors[$order->status] ?? 'bg-gray-100 text-gray-600';
    $statusLabel = str_replace('_', ' ', $order->status);
    $proofSubmitted = $order->latestPayment?->proof_path;
    $needsPayment = $order->status === 'pending_payment' && !$proofSubmitted;
@endphp

<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    <div class="flex items-center justify-between mb-8">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Order #{{ $order->id }}</h1>
            <p class="text-sm text-gray-500">{{ $order->created_at->format('M j, Y · g:i A') }}</p>
        </div>
        <span class="inline-block text-xs font-semibold px-3 py-1 rounded-full {{ $badge }}">
            {{ ucwords($statusLabel) }}
        </span>
    </div>

    @if($needsPayment)
    <div class="bg-amber-50 border border-amber-200 rounded-xl p-5 mb-6">
        <p class="font-semibold text-amber-900 mb-1">Action required</p>
        <p class="text-sm text-amber-800 mb-4">Confirm your phone number and address, then upload your payment proof.</p>
        <a href="{{ route('orders.payment', $order) }}"
           class="inline-block bg-gray-900 text-white text-sm font-medium px-5 py-2.5 rounded-lg hover:bg-gray-700 transition">
            Submit Payment Proof
        </a>
    </div>
    @elseif($order->status === 'pending_payment' && $proofSubmitted)
    <div class="bg-blue-50 border border-blue-200 rounded-xl p-5 mb-6 text-sm text-blue-900">
        <p class="font-semibold mb-1">Payment proof received</p>
        <p>We are verifying your payment. You will be notified once the order is confirmed.</p>
    </div>
    @endif

    <div class="bg-white rounded-xl border border-gray-100 p-6 mb-6">
        <h2 class="font-semibold text-gray-900 mb-4">Contact &amp; Shipping</h2>
        <dl class="space-y-3 text-sm">
            <div>
                <dt class="text-gray-500">Phone</dt>
                <dd class="font-medium text-gray-900">{{ $user->phone_num ?: '—' }}</dd>
            </div>
            <div>
                <dt class="text-gray-500">Address</dt>
                <dd class="font-medium text-gray-900 whitespace-pre-line">{{ $user->address ?: '—' }}</dd>
            </div>
        </dl>
        @if($needsPayment && (!$user->phone_num || !$user->address))
        <p class="text-xs text-amber-700 mt-3">Phone and address are required before submitting payment proof.</p>
        @endif
    </div>

    @if($order->paymentMethod)
    <div class="bg-white rounded-xl border border-gray-100 p-6 mb-6">
        <h2 class="font-semibold text-gray-900 mb-3">Payment — {{ $order->paymentMethod->name }}</h2>
        <p class="text-lg font-bold text-gray-900 mb-4">Amount: <x-money :amount="$order->total" /></p>

        @if($order->paymentMethod->instructions)
        <div class="text-sm text-gray-700 whitespace-pre-line border-t border-gray-50 pt-4">{{ $order->paymentMethod->instructions }}</div>
        @endif

        @if($order->paymentMethod->qrImageUrl())
        <div class="mt-4 flex justify-center">
            <img src="{{ $order->paymentMethod->qrImageUrl() }}" alt="Payment QR"
                 class="w-40 h-40 object-contain border border-gray-100 rounded-lg">
        </div>
        @endif
    </div>
    @endif

    <div class="bg-white rounded-xl border border-gray-100 p-6 mb-6">
        <h2 class="font-semibold text-gray-900 mb-4">Items</h2>
        <div class="space-y-3">
            @foreach($order->orderItems as $item)
            <div class="flex justify-between text-sm">
                <div>
                    <p class="font-medium text-gray-900">{{ $item->product_name_snapshot }}</p>
                    <p class="text-xs text-gray-400">{{ $item->sku_snapshot }} × {{ $item->quantity }}</p>
                </div>
                <span class="font-semibold"><x-money :amount="$item->final_price * $item->quantity" /></span>
            </div>
            @endforeach
        </div>
        <div class="mt-4 pt-4 border-t border-gray-100 flex justify-between font-bold text-gray-900">
            <span>Total</span>
            <span><x-money :amount="$order->total" /></span>
        </div>
    </div>

    @if($order->notes)
    <div class="bg-white rounded-xl border border-gray-100 p-6 mb-6">
        <h2 class="font-semibold text-gray-900 mb-2">Notes</h2>
        <p class="text-sm text-gray-600">{{ $order->notes }}</p>
    </div>
    @endif

    @if($order->latestPayment)
    <p class="text-xs text-gray-500 mb-6">
        Payment: {{ ucfirst($order->latestPayment->status) }}
        @if($proofSubmitted) · Proof submitted @endif
    </p>
    @endif

    <a href="{{ route('orders.index') }}" class="text-sm text-gray-500 hover:text-gray-700">← Back to My Orders</a>
</div>

@endsection
