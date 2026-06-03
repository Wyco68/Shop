@props(['name'])

@php
    $label = trim((string) $name);
    $initials = collect(explode(' ', $label))
        ->filter()
        ->take(2)
        ->map(fn ($word) => mb_strtoupper(mb_substr($word, 0, 1)))
        ->join('');
    if ($initials === '') {
        $initials = '?';
    }
    $hue = crc32(mb_strtolower($label)) % 360;
@endphp

<div {{ $attributes->merge(['class' => 'w-full h-full flex items-center justify-center rounded-full text-xs font-bold']) }}
     style="background-color: hsl({{ $hue }}, 55%, 92%); color: hsl({{ $hue }}, 45%, 35%);">
    {{ $initials }}
</div>
