@extends('layouts.admin')

@section('title', 'Manage Categories')

@section('content')
<div class="space-y-8">
    
    <!-- Top Action bar -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-white p-6 rounded-2xl border border-slate-100 shadow-sm shadow-slate-100/40">
        <div>
            <h1 class="text-2xl font-bold text-slate-800 tracking-tight">Categories Directory</h1>
            <p class="text-sm text-slate-500 mt-1">Organize products into distinct groupings, manage metadata, and toggle storefront display.</p>
        </div>
        <div class="shrink-0">
            <a href="{{ route('admin.products.index') }}" class="inline-flex items-center gap-2 px-4 py-2 border border-slate-200 text-slate-600 hover:text-slate-800 hover:bg-slate-50 rounded-xl font-medium text-sm transition">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Back to Catalog
            </a>
        </div>
    </div>

    <!-- Alert Messages (handled in layout, but leaving structure in place in case fallback is required) -->
    <!-- 2-Column Responsive Grid Layout -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        
        <!-- Left: Categories List (Spans 2 columns) -->
        <div class="lg:col-span-2">
            <x-admin.card title="All Categories">
                <x-admin.data-table class="w-full">
                    <thead>
                        <tr class="bg-slate-50/50 border-b border-slate-100">
                            <th class="px-6 py-4 text-xs font-bold uppercase tracking-wider text-slate-400">Icon</th>
                            <th class="px-6 py-4 text-xs font-bold uppercase tracking-wider text-slate-400">Category Name</th>
                            <th class="px-6 py-4 text-xs font-bold uppercase tracking-wider text-slate-400">Status</th>
                            <th class="px-6 py-4"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-sm">
                        @forelse($categories as $category)
                            <tr class="hover:bg-slate-50/50 transition">
                                <td class="px-6 py-4">
                                    <div class="w-10 h-10 rounded-full overflow-hidden bg-slate-100">
                                        <x-category-icon :name="$category->name" :icon-url="$category->iconUrl()" class="w-10 h-10" />
                                    </div>
                                </td>
                                <td class="px-6 py-4 font-bold text-slate-800">
                                    {{ $category->name }}
                                </td>
                                
                                <!-- Status -->
                                <td class="px-6 py-4">
                                    @if($category->is_active)
                                        <x-admin.status-badge status="active" />
                                    @else
                                        <x-admin.status-badge status="inactive" />
                                    @endif
                                </td>
                                
                                <!-- Actions -->
                                <td class="px-6 py-4 text-right">
                                    <div class="inline-flex items-center gap-3">
                                        <a href="{{ route('admin.categories.index', ['edit' => $category->id]) }}" 
                                           class="inline-flex items-center gap-1 px-3 py-1.5 border border-slate-100 text-sky-500 hover:bg-sky-50 rounded-xl text-xs font-bold transition">
                                            Edit
                                        </a>
                                        <form action="{{ route('admin.categories.destroy', $category) }}" method="POST" class="inline-block" onsubmit="return confirm('Delete this category? Products might be affected.');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="inline-flex items-center gap-1 px-3 py-1.5 border border-slate-100 text-rose-500 hover:bg-rose-50 rounded-xl text-xs font-bold transition">
                                                Delete
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-12 text-center text-slate-400 font-medium bg-slate-50/20">
                                    <div class="flex flex-col items-center justify-center gap-2">
                                        <svg class="w-10 h-10 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M7 7h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                        </svg>
                                        No categories found in system.
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </x-admin.data-table>
            </x-admin.card>
        </div>

        <!-- Right: Create/Edit Dynamic Form Card -->
        <div>
            @php
                $isEdit = request()->has('edit');
                $editCategory = $isEdit ? $categories->firstWhere('id', request('edit')) : null;
            @endphp
            
            <div class="sticky top-24">
                <x-admin.card title="{{ $isEdit ? 'Edit Category' : 'Create Category' }}">
                    <form action="{{ $isEdit ? route('admin.categories.update', $editCategory) : route('admin.categories.store') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
                        @csrf
                        @if($isEdit)
                            @method('PUT')
                        @endif
                        
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Category icon</label>
                            <x-admin.media-upload
                                name="icon"
                                id="category_icon"
                                :preview="$isEdit && $editCategory?->iconUrl() ? $editCategory->iconUrl() : ''"
                                accept="image/png,image/jpeg,image/webp,image/gif"
                                placeholder="Upload category icon"
                                :button="$isEdit ? 'Update icon' : 'Choose icon'"
                                hint="PNG, JPG, WebP, or GIF · max 512KB"
                                height="h-36"
                                preview-rounded="rounded-full"
                            />
                        </div>

                        <!-- Name input -->
                        <div>
                            <label for="name" class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Category Name</label>
                            <input type="text" name="name" id="name" 
                                   class="w-full px-4 py-3 rounded-xl border border-slate-200 text-slate-800 text-sm font-medium focus:outline-none focus:border-sky-500 focus:ring-4 focus:ring-sky-500/10 placeholder-slate-400/70 transition shadow-inner" 
                                   placeholder="e.g. Engine Parts"
                                   value="{{ old('name', $editCategory->name ?? '') }}" required>
                        </div>
                        
                        <!-- Status Active Checkbox Toggle -->
                        <div>
                            <span class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-3">Visibility status</span>
                            
                            <div class="flex items-center justify-between p-3.5 bg-slate-50 rounded-xl border border-slate-100">
                                <span class="text-sm font-semibold text-slate-700">Display in store</span>
                                
                                <div class="flex items-center">
                                    <input type="hidden" name="is_active" value="0">
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="checkbox" id="is_active" name="is_active" value="1" {{ old('is_active', $editCategory->is_active ?? true) ? 'checked' : '' }} class="sr-only peer">
                                        <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-sky-500/20 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-sky-500"></div>
                                    </label>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Actions button -->
                        <div class="flex items-center justify-between gap-4 pt-2">
                            <button type="submit" class="flex-1 inline-flex justify-center items-center py-2.5 px-4 border border-transparent shadow-sm text-xs font-bold uppercase tracking-wider rounded-xl text-white bg-sky-500 hover:bg-sky-600 focus:outline-none transition">
                                {{ $isEdit ? 'Update Category' : 'Create Category' }}
                            </button>
                            
                            @if($isEdit)
                                <a href="{{ route('admin.categories.index') }}" 
                                   class="px-4 py-2.5 border border-slate-200 text-slate-600 hover:text-slate-800 hover:bg-slate-50 rounded-xl font-bold text-xs uppercase tracking-wider transition">
                                    Cancel
                                </a>
                            @endif
                        </div>
                    </form>
                </x-admin.card>
            </div>
        </div>

    </div>
</div>
@endsection