@extends('layouts.admin')

@section('title', 'Payment settings')

@section('content')
<div class="space-y-8">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-white p-6 rounded-2xl border border-slate-100 shadow-sm">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">Payment configuration</h1>
            <p class="text-sm text-slate-500 mt-1">Enable methods and configure bank, mobile, or crypto details. Sensitive fields are encrypted at rest.</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <div class="lg:col-span-2">
            <x-admin.card title="Payment methods">
                <x-admin.data-table class="w-full">
                    <thead>
                        <tr class="bg-slate-50/50 border-b border-slate-100">
                            <th class="px-4 py-3 text-xs font-bold uppercase text-slate-400">Name</th>
                            <th class="px-4 py-3 text-xs font-bold uppercase text-slate-400">Type</th>
                            <th class="px-4 py-3 text-xs font-bold uppercase text-slate-400">Status</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-sm">
                        @forelse($paymentMethods as $method)
                        <tr>
                            <td class="px-4 py-3 font-semibold">{{ $method->name }}</td>
                            <td class="px-4 py-3 text-slate-500">{{ $method->type?->label() ?? $method->type }}</td>
                            <td class="px-4 py-3">
                                @if($method->is_active)
                                    <x-admin.status-badge status="active" />
                                @else
                                    <x-admin.status-badge status="inactive" />
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right space-x-2">
                                <a href="{{ route('admin.settings.payments.index', ['edit' => $method->id]) }}" class="text-sky-600 text-xs font-bold">Edit</a>
                                <form action="{{ route('admin.settings.payments.destroy', $method) }}" method="POST" class="inline" onsubmit="return confirm('Delete?');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-rose-600 text-xs font-bold">Delete</button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="4" class="px-4 py-8 text-center text-slate-400">No payment methods configured.</td></tr>
                        @endforelse
                    </tbody>
                </x-admin.data-table>
            </x-admin.card>
        </div>

        <div>
            @php
                $isEdit = request()->has('edit');
                $editMethod = $isEdit ? $paymentMethods->firstWhere('id', request('edit')) : null;
                $editType = old('type', $editMethod?->type?->value ?? 'bank');
            @endphp
            <div class="sticky top-24" x-data="{ type: '{{ $editType }}' }">
                <x-admin.card title="{{ $isEdit ? 'Edit method' : 'Add method' }}">
                    <form action="{{ $isEdit ? route('admin.settings.payments.update', $editMethod) : route('admin.settings.payments.store') }}"
                          method="POST" enctype="multipart/form-data" class="space-y-5">
                        @csrf
                        @if($isEdit) @method('PUT') @endif

                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Name</label>
                            <input type="text" name="name" required value="{{ old('name', $editMethod?->name) }}"
                                   class="w-full px-3 py-2 rounded-xl border border-slate-200 text-sm">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Type</label>
                            <select name="type" x-model="type" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-sm">
                                @foreach($types as $t)
                                    <option value="{{ $t->value }}">{{ $t->label() }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div x-show="type === 'bank'" class="space-y-2">
                            <input type="text" name="config[bank_name]" placeholder="Bank name" value="{{ old('config.bank_name', $editMethod?->config['bank_name'] ?? '') }}" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-sm">
                            <input type="text" name="config[account_name]" placeholder="Account name" value="{{ old('config.account_name', $editMethod?->config['account_name'] ?? '') }}" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-sm">
                            <input type="text" name="config[account_number]" placeholder="Account number" value="{{ old('config.account_number', $editMethod?->config['account_number'] ?? '') }}" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-sm">
                        </div>

                        <div x-show="type === 'mobile'" class="space-y-2" x-cloak>
                            <input type="text" name="config[mobile_provider]" placeholder="Provider (GCash, etc.)" value="{{ old('config.mobile_provider', $editMethod?->config['mobile_provider'] ?? '') }}" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-sm">
                            <input type="text" name="config[mobile_number]" placeholder="Mobile number" value="{{ old('config.mobile_number', $editMethod?->config['mobile_number'] ?? '') }}" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-sm">
                        </div>

                        <div x-show="type === 'crypto'" class="space-y-2" x-cloak>
                            <input type="text" name="config[network]" placeholder="Network" value="{{ old('config.network', $editMethod?->config['network'] ?? '') }}" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-sm">
                            <input type="text" name="config[wallet_address]" placeholder="Wallet address" value="{{ old('config.wallet_address', $editMethod?->config['wallet_address'] ?? '') }}" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-sm">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Instructions</label>
                            <textarea name="instructions" rows="3" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-sm">{{ old('instructions', $editMethod?->instructions) }}</textarea>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">QR code image</label>
                            <x-admin.media-upload
                                name="qr_image"
                                id="payment_qr_image"
                                :preview="$editMethod?->qrImageUrl() ?? ''"
                                accept="image/png,image/jpeg,image/webp,image/gif"
                                placeholder="Upload QR code"
                                :button="$isEdit ? 'Update QR image' : 'Choose QR image'"
                                :hint="$isEdit ? 'Leave empty to keep current image' : 'PNG, JPG, or WebP · shown at checkout'"
                                height="h-40"
                                object-class="object-contain"
                                preview-rounded="rounded-lg"
                            />
                        </div>

                        <label class="inline-flex items-center gap-2 text-sm">
                            <input type="hidden" name="is_active" value="0">
                            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $editMethod?->is_active ?? true))>
                            Enabled at checkout
                        </label>

                        <button type="submit" class="w-full py-2.5 bg-sky-600 text-white rounded-xl text-sm font-bold">
                            {{ $isEdit ? 'Update' : 'Create' }}
                        </button>
                        @if($isEdit)
                            <a href="{{ route('admin.settings.payments.index') }}" class="block text-center text-xs text-slate-500">Cancel</a>
                        @endif
                    </form>
                </x-admin.card>
            </div>
        </div>
    </div>
</div>
@endsection
