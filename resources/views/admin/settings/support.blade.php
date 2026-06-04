@extends('layouts.admin')

@section('title', 'Support contacts')

@section('content')
<div class="space-y-8">
    <div class="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm">
        <h1 class="text-2xl font-bold text-slate-800">Customer support</h1>
        <p class="text-sm text-slate-500 mt-1">Social links and QR codes shown in the footer, contact page, and checkout.</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <div class="lg:col-span-2">
            <x-admin.card title="Contact methods">
                <x-admin.data-table class="w-full">
                    <thead>
                        <tr class="bg-slate-50/50 border-b border-slate-100">
                            <th class="px-4 py-3 text-xs font-bold uppercase text-slate-400">Type</th>
                            <th class="px-4 py-3 text-xs font-bold uppercase text-slate-400">Username / label</th>
                            <th class="px-4 py-3 text-xs font-bold uppercase text-slate-400">Status</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-sm">
                        @forelse($supportContacts as $contact)
                        <tr>
                            <td class="px-4 py-3 font-semibold capitalize">{{ $contact->type?->label() ?? $contact->type }}</td>
                            <td class="px-4 py-3 text-slate-500">{{ $contact->username ?: '—' }}</td>
                            <td class="px-4 py-3">
                                @if($contact->enabled)
                                    <x-admin.status-badge status="active" />
                                @else
                                    <x-admin.status-badge status="inactive" />
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right space-x-2">
                                <a href="{{ route('admin.settings.support.index', ['edit' => $contact->id]) }}" class="text-sky-600 text-xs font-bold">Edit</a>
                                <form action="{{ route('admin.settings.support.destroy', $contact) }}" method="POST" class="inline" onsubmit="return confirm('Delete this contact?');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-rose-600 text-xs font-bold">Delete</button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="4" class="px-4 py-8 text-center text-slate-400">No support contacts yet.</td></tr>
                        @endforelse
                    </tbody>
                </x-admin.data-table>
            </x-admin.card>
        </div>

        <div>
            @php $isEdit = $editContact !== null; @endphp
            <div class="sticky top-24">
                <x-admin.card title="{{ $isEdit ? 'Edit contact' : 'Add contact' }}">
                    <form action="{{ $isEdit ? route('admin.settings.support.update', $editContact) : route('admin.settings.support.store') }}"
                          method="POST" enctype="multipart/form-data" class="space-y-5">
                        @csrf
                        @if($isEdit) @method('PUT') @endif

                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Type</label>
                            <select name="type" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-sm">
                                @foreach($types as $t)
                                    <option value="{{ $t->value }}" @selected(old('type', $editContact?->type?->value) === $t->value)>{{ $t->label() }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Username / display</label>
                            <input type="text" name="username" value="{{ old('username', $editContact?->username) }}"
                                   placeholder="@shop_support"
                                   class="w-full px-3 py-2 rounded-xl border border-slate-200 text-sm">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Link (https)</label>
                            <input type="url" name="link" value="{{ old('link', $editContact?->link) }}"
                                   placeholder="https://t.me/shop_support"
                                   class="w-full px-3 py-2 rounded-xl border border-slate-200 text-sm">
                            @error('link')<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">QR code</label>
                            <x-admin.media-upload
                                name="qr_image"
                                id="support_qr_image"
                                :preview="$editContact && $editContact->qr_path ? app(\App\Services\SupportContactService::class)->qrUrl($editContact->qr_path) : ''"
                                accept="image/png,image/jpeg,image/webp,image/gif"
                                placeholder="Upload QR code"
                                :button="$isEdit ? 'Update QR' : 'Choose QR image'"
                                hint="PNG, JPG, or WebP"
                                height="h-40"
                                object-class="object-contain"
                                preview-rounded="rounded-lg"
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Sort order</label>
                            <input type="number" name="sort_order" min="0" max="9999"
                                   value="{{ old('sort_order', $editContact?->sort_order ?? 0) }}"
                                   class="w-full px-3 py-2 rounded-xl border border-slate-200 text-sm">
                        </div>

                        <label class="inline-flex items-center gap-2 text-sm">
                            <input type="hidden" name="enabled" value="0">
                            <input type="checkbox" name="enabled" value="1" @checked(old('enabled', $editContact?->enabled ?? true))>
                            Enabled on storefront
                        </label>

                        <button type="submit" class="w-full py-2.5 bg-sky-600 text-white rounded-xl text-sm font-bold">
                            {{ $isEdit ? 'Update' : 'Create' }}
                        </button>
                        @if($isEdit)
                            <a href="{{ route('admin.settings.support.index') }}" class="block text-center text-xs text-slate-500">Cancel</a>
                        @endif
                    </form>
                </x-admin.card>
            </div>
        </div>
    </div>
</div>
@endsection
