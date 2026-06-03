@extends('layouts.admin')

@section('title', 'User Profile: ' . $profile['user']['name'])

@section('content')
<div class="space-y-6">

    {{-- Breadcrumb & Actions --}}
    <div class="flex items-center justify-between">
        <a href="{{ route('admin.users.index') }}" class="text-sm font-medium text-slate-500 hover:text-slate-700 transition flex items-center gap-1">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            Back to Customers
        </a>
    </div>

    {{-- 1. User Info Header --}}
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6 sm:p-8 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-6">
        <div class="flex items-center gap-5">
            <div class="w-20 h-20 rounded-2xl bg-gradient-to-br from-sky-400 to-indigo-500 flex items-center justify-center text-white font-bold text-3xl shadow-md shrink-0">
                {{ strtoupper(substr($profile['user']['name'], 0, 1)) }}
            </div>
            <div>
                <h1 class="text-2xl font-bold text-slate-800 tracking-tight">{{ $profile['user']['name'] }}</h1>
                <div class="mt-2 flex flex-col sm:flex-row sm:items-center gap-2 sm:gap-4 text-sm text-slate-500">
                    <span class="flex items-center gap-1.5">
                        <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                        </svg>
                        {{ $profile['user']['email'] }}
                    </span>
                    <span class="hidden sm:inline text-slate-300">•</span>
                    <span class="flex items-center gap-1.5">
                        <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        Joined {{ \Carbon\Carbon::parse($profile['user']['created_at'])->format('M d, Y') }}
                    </span>
                </div>
            </div>
        </div>
        
        <div class="shrink-0 flex items-center justify-center px-4 py-2 rounded-xl border border-slate-100 shadow-sm
            @if($profile['tier']['raw'] === 'gold') bg-amber-50 text-amber-600 border-amber-200
            @elseif($profile['tier']['raw'] === 'silver') bg-slate-50 text-slate-600 border-slate-200
            @else bg-orange-50 text-orange-700 border-orange-200 @endif">
            <span class="font-bold uppercase tracking-wider text-sm flex items-center gap-2">
                @if($profile['tier']['raw'] === 'gold')
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5 2a1 1 0 011 1v1h1a1 1 0 010 2H6v1a1 1 0 01-2 0V6H3a1 1 0 010-2h1V3a1 1 0 011-1zm0 10a1 1 0 011 1v1h1a1 1 0 110 2H6v1a1 1 0 11-2 0v-1H3a1 1 0 110-2h1v-1a1 1 0 011-1zM12 2a1 1 0 01.967.744L14.146 7.2 17.5 9.134a1 1 0 010 1.732l-3.354 1.935-1.18 4.455a1 1 0 01-1.933 0L9.854 12.8 6.5 10.866a1 1 0 010-1.732l3.354-1.935 1.18-4.455A1 1 0 0112 2z" clip-rule="evenodd"/></svg>
                @endif
                {{ $profile['tier']['name'] }} Member
            </span>
        </div>
    </div>

    {{-- 2. Financial Summary Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        <x-admin.stat-card title="Total Spent" value="{{ \App\Support\Money::format($profile['metrics']['total_spent']) }}" color="emerald">
            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
        </x-admin.stat-card>

        <x-admin.stat-card title="Total Orders" value="{{ $profile['metrics']['total_orders'] }}" color="sky">
            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
            </svg>
        </x-admin.stat-card>

        <x-admin.stat-card title="Avg Order Value" value="{{ \App\Support\Money::format($profile['metrics']['average_order_value']) }}" color="indigo">
            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
            </svg>
        </x-admin.stat-card>

        <x-admin.stat-card title="Last Order" value="{{ $profile['metrics']['last_order_date'] ? \Carbon\Carbon::parse($profile['metrics']['last_order_date'])->diffForHumans() : 'Never' }}" color="slate">
            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
        </x-admin.stat-card>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <div class="lg:col-span-1 space-y-8">
            {{-- 3. Tier Progress Bar --}}
            <x-admin.card title="Tier Progress">
                @if($profile['tier']['next_threshold'] === null)
                    <div class="text-center py-6">
                        <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-amber-100 text-amber-500 mb-3">
                            <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm4 8a4 4 0 11-8 0 4 4 0 018 0zm-.464 4.95l.707.707a1 1 0 001.414-1.414l-.707-.707a1 1 0 00-1.414 1.414zm2.12-10.607a1 1 0 010 1.414l-.706.707a1 1 0 11-1.414-1.414l.707-.707a1 1 0 011.414 0zM17 11a1 1 0 100-2h-1a1 1 0 100 2h1zm-7 4a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zM5.05 6.464A1 1 0 106.465 5.05l-.708-.707a1 1 0 00-1.414 1.414l.707.707zm1.414 8.486l-.707.707a1 1 0 01-1.414-1.414l.707-.707a1 1 0 011.414 1.414zM4 11a1 1 0 100-2H3a1 1 0 000 2h1z" clip-rule="evenodd"/></svg>
                        </div>
                        <h3 class="text-lg font-bold text-slate-800">Top Tier Reached</h3>
                        <p class="text-sm text-slate-500 mt-1">This user has reached the highest membership tier (Gold).</p>
                    </div>
                @else
                    <div class="mt-2">
                        <div class="flex justify-between text-sm mb-2">
                            <span class="font-semibold text-slate-700">Current: {{ $profile['tier']['name'] }}</span>
                            <span class="text-slate-500">Next: {{ $profile['tier']['next_tier_name'] }}</span>
                        </div>
                        <div class="w-full bg-slate-100 rounded-full h-3 mb-3 border border-slate-200 overflow-hidden">
                            <div class="bg-gradient-to-r from-sky-400 to-indigo-500 h-3 rounded-full transition-all duration-500 relative" style="width: {{ $profile['tier']['progress_percent'] }}%">
                                <div class="absolute inset-0 bg-white/20" style="background-image: linear-gradient(45deg,rgba(255,255,255,.15) 25%,transparent 25%,transparent 50%,rgba(255,255,255,.15) 50%,rgba(255,255,255,.15) 75%,transparent 75%,transparent); background-size: 1rem 1rem;"></div>
                            </div>
                        </div>
                        <p class="text-xs text-slate-500 text-center">
                            <span class="font-bold text-slate-700"><x-money :amount="$profile['tier']['remaining']" /></span> away from {{ $profile['tier']['next_tier_name'] }}
                        </p>
                    </div>
                @endif
            </x-admin.card>

            {{-- 4. Order Status Breakdown --}}
            <x-admin.card title="Order Statistics">
                <div class="space-y-4 mt-2">
                    <div class="flex items-center justify-between p-3 rounded-xl bg-slate-50 border border-slate-100">
                        <span class="text-sm font-medium text-slate-600 flex items-center gap-2">
                            <span class="w-2.5 h-2.5 rounded-full bg-amber-400"></span> Pending/Processing
                        </span>
                        <span class="font-bold text-slate-800">{{ $profile['order_stats']['pending'] + $profile['order_stats']['processing'] }}</span>
                    </div>
                    
                    <div class="flex items-center justify-between p-3 rounded-xl bg-slate-50 border border-slate-100">
                        <span class="text-sm font-medium text-slate-600 flex items-center gap-2">
                            <span class="w-2.5 h-2.5 rounded-full bg-blue-400"></span> Confirmed/Shipped
                        </span>
                        <span class="font-bold text-slate-800">{{ $profile['order_stats']['confirmed'] + $profile['order_stats']['shipped'] }}</span>
                    </div>

                    <div class="flex items-center justify-between p-3 rounded-xl bg-slate-50 border border-slate-100">
                        <span class="text-sm font-medium text-slate-600 flex items-center gap-2">
                            <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span> Delivered
                        </span>
                        <span class="font-bold text-slate-800">{{ $profile['order_stats']['delivered'] }}</span>
                    </div>

                    <div class="flex items-center justify-between p-3 rounded-xl bg-slate-50 border border-slate-100">
                        <span class="text-sm font-medium text-slate-600 flex items-center gap-2">
                            <span class="w-2.5 h-2.5 rounded-full bg-rose-500"></span> Cancelled/Refunded
                        </span>
                        <span class="font-bold text-slate-800">{{ $profile['order_stats']['cancelled'] + $profile['order_stats']['refunded'] }}</span>
                    </div>
                </div>
            </x-admin.card>
        </div>

        <div class="lg:col-span-2">
            {{-- 5. Recent Orders Table --}}
            <x-admin.card title="Recent Orders">
                <x-slot name="action">
                    <a href="{{ route('admin.orders.index', ['user_id' => $profile['user']['id']]) }}" class="text-xs font-semibold text-sky-500 hover:text-sky-600 hover:underline transition">View All &rarr;</a>
                </x-slot>

                <div class="overflow-x-auto">
                    <x-admin.data-table class="w-full min-w-full">
                        <thead>
                            <tr class="bg-slate-50/50 border-b border-slate-100">
                                <th class="px-6 py-4 text-xs font-bold uppercase tracking-wider text-slate-400 text-left">Order ID</th>
                                <th class="px-6 py-4 text-xs font-bold uppercase tracking-wider text-slate-400 text-left">Date</th>
                                <th class="px-6 py-4 text-xs font-bold uppercase tracking-wider text-slate-400 text-left">Status</th>
                                <th class="px-6 py-4 text-xs font-bold uppercase tracking-wider text-slate-400 text-right">Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-sm">
                            @forelse($profile['recent_orders'] as $order)
                                <tr class="hover:bg-slate-50/50 transition">
                                    <td class="px-6 py-4 font-semibold text-slate-800 whitespace-nowrap">
                                        <a href="{{ route('admin.orders.show', $order['id']) }}" class="text-sky-500 hover:text-sky-600 hover:underline">
                                            #{{ $order['id'] }}
                                        </a>
                                    </td>
                                    <td class="px-6 py-4 text-slate-500 whitespace-nowrap">
                                        {{ \Carbon\Carbon::parse($order['created_at'])->format('M d, Y') }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <x-admin.status-badge :status="$order['status']" />
                                    </td>
                                    <td class="px-6 py-4 font-bold text-slate-800 text-right whitespace-nowrap">
                                        <x-money :amount="$order['total']" />
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-6 py-12 text-center text-slate-400 font-medium bg-slate-50/30">
                                        <div class="flex flex-col items-center justify-center gap-2">
                                            <svg class="w-8 h-8 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                            </svg>
                                            No orders placed yet.
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </x-admin.data-table>
                </div>
            </x-admin.card>
        </div>
    </div>
</div>
@endsection
