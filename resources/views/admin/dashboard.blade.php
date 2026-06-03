@extends('layouts.admin')

@section('title', 'Admin Dashboard')

@section('content')
<div class="space-y-8">
    <!-- Welcome Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 bg-white p-6 rounded-2xl border border-slate-100 shadow-sm shadow-slate-100/40">
        <div>
            <h1 class="text-2xl font-bold text-slate-800 tracking-tight">Overview</h1>
            <p class="text-sm text-slate-500 mt-1">Here is what is happening with your store today.</p>
        </div>
        <div class="flex items-center gap-2 text-xs font-semibold text-slate-500 bg-slate-50 border border-slate-100 px-3 py-1.5 rounded-xl self-start md:self-auto shadow-sm">
            <span class="w-2.5 h-2.5 bg-emerald-500 rounded-full animate-pulse"></span>
            System Live
        </div>
    </div>

    @if(($setupHints['needs_categories'] ?? false) || ($setupHints['needs_products'] ?? false) || ($setupHints['needs_payment_methods'] ?? false))
    <div class="bg-amber-50 border border-amber-200 rounded-2xl p-6">
        <h2 class="text-lg font-bold text-amber-900">Complete your store setup</h2>
        <p class="text-sm text-amber-800 mt-1">Add the essentials so customers can browse and checkout.</p>
        <ul class="mt-4 space-y-2 text-sm font-medium text-amber-900">
            @if($setupHints['needs_categories'])
            <li><a href="{{ route('admin.categories.index') }}" class="underline hover:no-underline">Create categories</a></li>
            @endif
            @if($setupHints['needs_products'])
            <li><a href="{{ route('admin.products.create') }}" class="underline hover:no-underline">Add products</a></li>
            @endif
            @if($setupHints['needs_payment_methods'])
            <li><a href="{{ route('admin.settings.payments.index') }}" class="underline hover:no-underline">Configure payment methods</a></li>
            @endif
        </ul>
    </div>
    @endif

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        <x-admin.stat-card 
            title="Total Orders" 
            value="{{ $stats['total_orders'] }}" 
            href="{{ route('admin.orders.index') }}"
            color="slate">
            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
            </svg>
        </x-admin.stat-card>

        <x-admin.stat-card 
            title="Pending Orders" 
            value="{{ $stats['pending_orders'] }}" 
            href="{{ route('admin.orders.index') }}"
            color="amber">
            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
        </x-admin.stat-card>

        <x-admin.stat-card 
            title="Total Revenue" 
            value="{{ \App\Support\Money::format($stats['revenue']) }}" 
            href="{{ route('admin.orders.index') }}"
            color="emerald">
            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
        </x-admin.stat-card>

        <x-admin.stat-card 
            title="Pending Payments" 
            value="{{ $stats['pending_payments'] }}" 
            href="{{ route('admin.orders.index') }}"
            color="sky">
            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
            </svg>
        </x-admin.stat-card>
    </div>

    <!-- Tables Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Recent Orders -->
        <x-admin.card title="Recent Orders">
            <x-slot name="action">
                <a href="{{ route('admin.orders.index') }}" class="text-xs font-semibold text-sky-500 hover:text-sky-600 hover:underline transition">View All Orders &rarr;</a>
            </x-slot>

            <x-admin.data-table class="w-full">
                <thead>
                    <tr class="bg-slate-50/50 border-b border-slate-100">
                        <th class="px-6 py-4 text-xs font-bold uppercase tracking-wider text-slate-400">Order ID</th>
                        <th class="px-6 py-4 text-xs font-bold uppercase tracking-wider text-slate-400">Customer</th>
                        <th class="px-6 py-4 text-xs font-bold uppercase tracking-wider text-slate-400">Total</th>
                        <th class="px-6 py-4 text-xs font-bold uppercase tracking-wider text-slate-400">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-sm">
                    @forelse($recentOrders->sortByDesc('created_at') as $order)
                        <tr class="hover:bg-slate-50/50 transition">
                            <td class="px-6 py-4 font-semibold text-slate-800">
                                <a href="{{ route('admin.orders.show', $order) }}" class="text-sky-500 hover:text-sky-600 hover:underline">
                                    #{{ $order->id }}
                                </a>
                            </td>
                            <td class="px-6 py-4 text-slate-600 font-medium">
                                {{ $order->user->name ?? 'Guest' }}
                            </td>
                            <td class="px-6 py-4 font-bold text-slate-800">
                                <x-money :amount="$order->total" />
                            </td>
                            <td class="px-6 py-4">
                                <x-admin.status-badge :status="$order->status" />
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-6 py-8 text-center text-slate-400 font-medium bg-slate-50/30">
                                <div class="flex flex-col items-center justify-center gap-2">
                                    <svg class="w-8 h-8 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                    </svg>
                                    No recent orders found.
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </x-admin.data-table>
        </x-admin.card>

        <!-- Low Stock Alerts -->
        <x-admin.card>
            <x-slot name="title">
                <span class="flex items-center gap-2 text-rose-600">
                    <svg class="w-5 h-5 shrink-0 animate-bounce" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    Low Stock Alerts
                </span>
            </x-slot>
            <x-slot name="action">
                <a href="{{ route('admin.products.index') }}" class="text-xs font-semibold text-slate-500 hover:text-slate-600 hover:underline transition">Manage Inventory &rarr;</a>
            </x-slot>

            <x-admin.data-table class="w-full">
                <thead>
                    <tr class="bg-slate-50/50 border-b border-slate-100">
                        <th class="px-6 py-4 text-xs font-bold uppercase tracking-wider text-slate-400">Product / Variant</th>
                        <th class="px-6 py-4 text-xs font-bold uppercase tracking-wider text-slate-400">SKU</th>
                        <th class="px-6 py-4 text-xs font-bold uppercase tracking-wider text-slate-400">Stock</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-sm">
                    @forelse($lowStock as $inventory)
                        <tr class="hover:bg-slate-50/50 transition">
                            <td class="px-6 py-4 font-semibold text-slate-800">
                                <div class="font-medium text-slate-800">{{ $inventory->variant->product->name }}</div>
                                <div class="text-xs text-slate-400 font-normal mt-0.5">{{ $inventory->variant->name }}</div>
                            </td>
                            <td class="px-6 py-4 text-slate-500 font-mono font-medium">
                                {{ $inventory->variant->sku }}
                            </td>
                            <td class="px-6 py-4">
                                <x-admin.status-badge status="low_stock">
                                    {{ $inventory->stock_quantity }} left
                                </x-admin.status-badge>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-6 py-8 text-center text-slate-400 font-medium bg-slate-50/30">
                                <div class="flex flex-col items-center justify-center gap-2">
                                    <svg class="w-8 h-8 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    All inventory levels are healthy!
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </x-admin.data-table>
        </x-admin.card>
    </div>
</div>
@endsection
