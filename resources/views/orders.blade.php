@extends('layouts.app')
@section('title', 'My Orders')
@section('content')

<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    <div class="flex items-center justify-between mb-8 border-b border-gray-100 pb-6">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 tracking-tight">My Orders</h1>
            @php $allOrdersTotal = Auth::user()->orders()->sum('total'); @endphp
            @if($allOrdersTotal > 0)
                <p class="text-sm text-gray-500 mt-2">Lifetime Total: <span class="font-bold text-gray-900"><x-money :amount="$allOrdersTotal" /></span></p>
            @endif
        </div>
        <a href="{{ route('products.index') }}"
           class="text-sm border border-gray-200 text-gray-700 px-5 py-2.5 rounded-full hover:border-black hover:text-black transition-colors font-medium">
            Continue Shopping
        </a>
    </div>

    @if($orders->isEmpty())
        <div class="text-center py-20 bg-white rounded-xl border border-gray-100">
            <svg class="w-16 h-16 text-gray-200 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
            </svg>
            <h2 class="text-lg font-semibold text-gray-700 mb-2">No orders yet</h2>
            <p class="text-sm text-gray-400 mb-6">Place your first order to see it here.</p>
            <a href="{{ route('products.index') }}"
               class="inline-block bg-gray-900 text-white font-medium px-6 py-2.5 rounded-xl hover:bg-gray-700 transition">
                Browse Products
            </a>
        </div>
    @else
        <div class="space-y-4">
            @foreach($orders as $order)
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
            @endphp
            <div class="bg-white rounded-xl border border-gray-100 p-6">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
                    <div>
                        <a href="{{ route('orders.show', $order) }}" class="text-xs text-gray-900 font-medium hover:underline">
                            Order #{{ $order->id }} — Track order
                        </a>
                        <p class="text-sm text-gray-500">{{ $order->created_at->format('M j, Y · g:i A') }}</p>
                    </div>
                    <div class="flex items-center gap-4">
                        <div class="text-right">
                            <span class="text-xs text-gray-500 uppercase tracking-wider font-semibold block mb-0.5">Order Total</span>
                            <span class="text-xl font-bold text-gray-900"><x-money :amount="$order->total" /></span>
                        </div>
                        <span class="inline-block text-xs font-semibold px-3 py-1 rounded-full {{ $badge }}">
                            {{ ucwords($statusLabel) }}
                        </span>
                    </div>
                </div>

                @if($order->orderItems->isNotEmpty())
                <div class="border-t border-gray-50 pt-3 mt-3">
                    <div class="space-y-1">
                        @foreach($order->orderItems->take(3) as $item)
                        <div class="flex justify-between text-sm text-gray-600">
                            <span class="line-clamp-1">{{ $item->product_name_snapshot }} × {{ $item->quantity }}</span>
                            <span><x-money :amount="$item->final_price * $item->quantity" /></span>
                        </div>
                        @endforeach
                        @if($order->orderItems->count() > 3)
                        <p class="text-xs text-gray-400">+{{ $order->orderItems->count() - 3 }} more item(s)</p>
                        @endif
                    </div>
                </div>
                @endif

                @if($order->status === 'pending_payment' && !$order->latestPayment?->proof_path)
                <div class="mt-3 pt-3 border-t border-gray-50">
                    <a href="{{ route('orders.payment', $order) }}"
                       class="text-xs font-medium text-amber-700 hover:underline">
                        Submit payment proof →
                    </a>
                </div>
                @elseif($order->paymentMethod)
                <div class="mt-3 pt-3 border-t border-gray-50 text-xs text-gray-500">
                    Payment method: <span class="font-medium text-gray-700">{{ $order->paymentMethod->name }}</span>
                    @if($order->latestPayment?->proof_path)
                    · Proof submitted
                    @endif
                </div>
                @endif
            </div>
            @endforeach
        </div>

        <div class="mt-8">
            {{ $orders->links() }}
        </div>
    @endif
</div>

@endsection
