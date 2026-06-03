@extends('layouts.admin')

@section('title', 'Payment Methods')

@section('content')
<div class="space-y-8">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-white p-6 rounded-2xl border border-slate-100 shadow-sm">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">Payment Methods</h1>
            <p class="text-sm text-slate-500 mt-1">Configure manual payment options shown at checkout.</p>
        </div>
        <a href="{{ route('admin.payment-methods.create') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-sky-600 hover:bg-sky-700 text-white rounded-xl font-medium text-sm transition">
            Add method
        </a>
    </div>

    <x-admin.card title="All methods">
        <x-admin.data-table class="w-full">
            <thead>
                <tr class="bg-slate-50/50 border-b border-slate-100">
                    <th class="px-6 py-4 text-xs font-bold uppercase text-slate-400">Name</th>
                    <th class="px-6 py-4 text-xs font-bold uppercase text-slate-400">Code</th>
                    <th class="px-6 py-4 text-xs font-bold uppercase text-slate-400">Status</th>
                    <th class="px-6 py-4"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-sm">
                @forelse($paymentMethods as $method)
                <tr class="hover:bg-slate-50/50">
                    <td class="px-6 py-4 font-semibold text-slate-800">{{ $method->name }}</td>
                    <td class="px-6 py-4 text-slate-500 font-mono text-xs">{{ $method->code }}</td>
                    <td class="px-6 py-4">
                        @if($method->is_active)
                            <x-admin.status-badge status="active" />
                        @else
                            <x-admin.status-badge status="inactive" />
                        @endif
                    </td>
                    <td class="px-6 py-4 text-right space-x-2">
                        <a href="{{ route('admin.payment-methods.edit', $method) }}" class="text-sky-600 font-semibold text-xs hover:underline">Edit</a>
                        <form action="{{ route('admin.payment-methods.destroy', $method) }}" method="POST" class="inline" onsubmit="return confirm('Delete this payment method?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-rose-600 font-semibold text-xs hover:underline">Delete</button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" class="px-6 py-8 text-center text-slate-500">No payment methods yet. Add one to enable checkout.</td>
                </tr>
                @endforelse
            </tbody>
        </x-admin.data-table>
    </x-admin.card>
</div>
@endsection
