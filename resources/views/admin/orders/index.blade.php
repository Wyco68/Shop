@extends('layouts.admin')

@section('title', 'Manage Orders')

@section('content')
<div class="space-y-8">
    
    <!-- Top Action bar -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 bg-white p-6 rounded-2xl border border-slate-100 shadow-sm shadow-slate-100/40">
        <div>
            <h1 class="text-2xl font-bold text-slate-800 tracking-tight">Order Management</h1>
            <p class="text-sm text-slate-500 mt-1">Review orders, verify payments, update delivery status, and manage invoices.</p>
        </div>
        <div class="shrink-0">
            <p class="text-xs font-semibold text-slate-400 bg-slate-50 border border-slate-100 px-3 py-2 rounded-xl">
                {{ $orders->total() }} order{{ $orders->total() !== 1 ? 's' : '' }} total
            </p>
        </div>
    </div>

    <!-- Main Card Container -->
    <x-admin.card title="Orders List">
        <x-slot name="action">
            <!-- Filter Form -->
            <form method="GET" action="{{ route('admin.orders.index') }}" class="flex items-center gap-3">
                <div class="relative shrink-0">
                    <select name="status" id="status" 
                            class="pl-4 pr-10 py-2 border border-slate-200 bg-white hover:bg-slate-50 rounded-xl text-xs font-semibold text-slate-600 focus:outline-none focus:border-sky-500 focus:ring-4 focus:ring-sky-500/10 transition shadow-sm" 
                            onchange="this.form.submit()">
                        <option value="">All Statuses</option>
                        <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="pending_payment" {{ request('status') == 'pending_payment' ? 'selected' : '' }}>Pending Payment</option>
                        <option value="paid" {{ request('status') == 'paid' ? 'selected' : '' }}>Paid</option>
                        <option value="processing" {{ request('status') == 'processing' ? 'selected' : '' }}>Processing</option>
                        <option value="shipped" {{ request('status') == 'shipped' ? 'selected' : '' }}>Shipped</option>
                        <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Completed</option>
                        <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                        <option value="refunded" {{ request('status') == 'refunded' ? 'selected' : '' }}>Refunded</option>
                    </select>
                </div>
                @if(request('status'))
                    <a href="{{ route('admin.orders.index') }}" class="inline-flex items-center gap-1 text-xs font-bold text-rose-500 hover:text-rose-600 transition">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                        Clear
                    </a>
                @endif
            </form>
        </x-slot>

        <!-- Orders Table -->
        <x-admin.data-table class="w-full">
            <thead>
                <tr class="bg-slate-50/50 border-b border-slate-100">
                    <th class="px-6 py-4 text-xs font-bold uppercase tracking-wider text-slate-400">Order ID</th>
                    <th class="px-6 py-4 text-xs font-bold uppercase tracking-wider text-slate-400">Customer</th>
                    <th class="px-6 py-4 text-xs font-bold uppercase tracking-wider text-slate-400">Date</th>
                    <th class="px-6 py-4 text-xs font-bold uppercase tracking-wider text-slate-400">Total</th>
                    <th class="px-6 py-4 text-xs font-bold uppercase tracking-wider text-slate-400">Payment</th>
                    <th class="px-6 py-4 text-xs font-bold uppercase tracking-wider text-slate-400">Status</th>
                    <th class="px-6 py-4"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-sm">
                @forelse($orders as $order)
                    <tr class="hover:bg-slate-50/50 transition">
                        <!-- Order ID -->
                        <td class="px-6 py-4 font-bold text-slate-800">
                            #{{ $order->id }}
                        </td>
                        
                        <!-- Customer -->
                        <td class="px-6 py-4">
                            <div class="font-semibold text-slate-800">{{ $order->user->name ?? 'Guest' }}</div>
                            <div class="text-xs text-slate-400 mt-0.5 font-medium">{{ $order->user->email ?? '' }}</div>
                        </td>
                        
                        <!-- Date -->
                        <td class="px-6 py-4 text-slate-500 font-medium">
                            {{ $order->created_at->format('M d, Y H:i') }}
                        </td>
                        
                        <!-- Total -->
                        <td class="px-6 py-4 font-extrabold text-slate-800">
                            <x-money :amount="$order->total" />
                        </td>
                        
                        <!-- Payment -->
                        <td class="px-6 py-4">
                            @if($order->latestPayment)
                                <div class="font-semibold text-slate-700 text-xs">{{ ucfirst($order->latestPayment->payment_method) }}</div>
                                <div class="text-xxs font-bold mt-1">
                                    @if($order->latestPayment->status === 'verified')
                                        <span class="px-2 py-0.5 rounded bg-emerald-50 text-emerald-700 border border-emerald-100 uppercase tracking-wider">Verified</span>
                                    @elseif($order->latestPayment->status === 'pending')
                                        <span class="px-2 py-0.5 rounded bg-amber-50 text-amber-700 border border-amber-100 uppercase tracking-wider">Pending Review</span>
                                    @else
                                        <span class="px-2 py-0.5 rounded bg-rose-50 text-rose-700 border border-rose-100 uppercase tracking-wider">{{ $order->latestPayment->status }}</span>
                                    @endif
                                </div>
                            @else
                                <span class="text-slate-400 text-xs font-semibold uppercase tracking-wider">N/A</span>
                            @endif
                        </td>
                        
                        <!-- Status Badge -->
                        <td class="px-6 py-4">
                            <x-admin.status-badge :status="$order->status" />
                        </td>
                        
                        <!-- Action -->
                        <td class="px-6 py-4 text-right">
                            <a href="{{ route('admin.orders.show', $order) }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 border border-slate-100 text-slate-600 hover:text-sky-500 hover:bg-slate-50 rounded-xl text-xs font-bold transition">
                                View Details
                                <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                </svg>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center text-slate-400 font-medium bg-slate-50/20">
                            <div class="flex flex-col items-center justify-center gap-2">
                                <svg class="w-10 h-10 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                </svg>
                                No orders found matching the criteria.
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </x-admin.data-table>

        <!-- Pagination -->
        <div class="mt-6 flex flex-col sm:flex-row items-center justify-between gap-4">
            <div class="text-xs text-slate-500 font-semibold uppercase tracking-wider">
                Showing Page {{ $orders->currentPage() }} of {{ $orders->lastPage() }}
            </div>
            <div class="w-full sm:w-auto">
                {{ $orders->links() }}
            </div>
        </div>
    </x-admin.card>

</div>
@endsection
