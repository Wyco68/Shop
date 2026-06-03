@extends('layouts.admin')

@section('title', 'Branding')

@section('content')
<div class="max-w-3xl mx-auto space-y-8" x-data="{ faviconPreview: null, logoPreview: null }">
    <div class="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm">
        <h1 class="text-2xl font-bold text-slate-800">Store branding</h1>
        <p class="text-sm text-slate-500 mt-1">Upload favicon and logo</p>
    </div>

    <x-admin.card title="Assets">
        <form action="{{ route('admin.settings.branding.update') }}" method="POST" enctype="multipart/form-data" class="space-y-8">
            @csrf
            @method('PUT')

            <p class="text-sm text-slate-600">Current shop name: <strong>{{ config('shop.name') }}</strong></p>

            <div class="grid sm:grid-cols-2 gap-8">
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-3">Favicon</label>
                    <div class="flex items-start gap-4">
                        <div class="w-16 h-16 rounded-xl border border-slate-200 bg-slate-50 flex items-center justify-center overflow-hidden shrink-0">
                            <img :src="faviconPreview || '{{ $faviconUrl }}'" alt="Favicon preview" class="max-w-full max-h-full object-contain">
                        </div>
                        <div class="flex-1">
                            <input type="file" name="favicon" accept=".ico,.png,.jpg,.jpeg,.gif,.webp"
                                   @change="faviconPreview = $event.target.files[0] ? URL.createObjectURL($event.target.files[0]) : null"
                                   class="block w-full text-sm text-slate-600 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-slate-100 file:font-semibold hover:file:bg-slate-200">
                            <p class="text-xs text-slate-400 mt-2">PNG or ICO, max 1MB.</p>
                            @error('favicon')<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror
                        </div>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-3">Logo</label>
                    <div class="flex items-start gap-4">
                        <div class="w-24 h-16 rounded-xl border border-slate-200 bg-slate-50 flex items-center justify-center overflow-hidden shrink-0 p-2">
                            <img :src="logoPreview || '{{ $logoUrl }}'" alt="Logo preview" class="max-w-full max-h-full object-contain">
                        </div>
                        <div class="flex-1">
                            <input type="file" name="logo" accept=".png,.jpg,.jpeg,.gif,.webp"
                                   @change="logoPreview = $event.target.files[0] ? URL.createObjectURL($event.target.files[0]) : null"
                                   class="block w-full text-sm text-slate-600 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-slate-100 file:font-semibold hover:file:bg-slate-200">
                            <p class="text-xs text-slate-400 mt-2">PNG, JPG, or WebP, max 1MB.</p>
                            @error('logo')<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex gap-3 pt-2 border-t border-slate-100">
                <button type="submit" class="px-6 py-2.5 bg-sky-600 hover:bg-sky-700 text-white rounded-xl font-semibold text-sm transition">
                    Save branding
                </button>
            </div>
        </form>
    </x-admin.card>

    <p class="text-xs text-slate-500">Administrator passwords are managed only via CLI: <code class="bg-slate-100 px-1 rounded">php artisan admin:change-password</code></p>
</div>
@endsection
