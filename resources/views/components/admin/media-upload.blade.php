@props([
    'name' => 'image',
    'id' => null,
    'preview' => '',
    'accept' => 'image/*',
    'placeholder' => 'Upload image',
    'button' => 'Choose File',
    'hint' => null,
    'height' => 'h-44',
    'objectClass' => 'object-cover',
    'previewRounded' => 'rounded-xl',
])

@php
    $inputId = $id ?? 'media_' . preg_replace('/[^a-z0-9_]/i', '_', $name);
@endphp

<div {{ $attributes->merge(['class' => 'space-y-4']) }} x-data="{ preview: @js($preview) }">
    <div class="{{ $height }} border-2 border-dashed border-slate-200 hover:border-sky-400 transition-colors rounded-2xl flex items-center justify-center p-3 relative bg-slate-50 overflow-hidden group">
        <template x-if="preview">
            <img :src="preview" alt="" class="h-full w-full {{ $objectClass }} {{ $previewRounded }} group-hover:scale-105 transition duration-300">
        </template>
        <template x-if="!preview">
            <div class="text-center p-4">
                <svg class="w-10 h-10 text-slate-300 mx-auto mb-2 group-hover:text-sky-500 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                <span class="text-xs font-semibold text-slate-500">{{ $placeholder }}</span>
            </div>
        </template>
    </div>

    <div>
        <input type="file"
               name="{{ $name }}"
               id="{{ $inputId }}"
               accept="{{ $accept }}"
               class="hidden"
               @change="const file = $event.target.files[0]; if (file) { preview = URL.createObjectURL(file); }">
        <label for="{{ $inputId }}"
               class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 border border-slate-200 text-slate-600 hover:text-slate-800 hover:bg-slate-50 rounded-xl font-semibold text-xs uppercase tracking-wider cursor-pointer shadow-sm transition">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
            </svg>
            {{ $button }}
        </label>
        @if($hint)
            <p class="text-[10px] text-slate-400 font-semibold text-center mt-2 uppercase tracking-wide">{{ $hint }}</p>
        @endif
        @if($errors->has($name))
            <p class="text-xs text-rose-600 text-center mt-2">{{ $errors->first($name) }}</p>
        @endif
    </div>
</div>
