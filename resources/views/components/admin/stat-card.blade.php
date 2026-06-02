@props([
    'title',
    'value',
    'href' => '#',
    'color' => 'sky',
])

<a href="{{ $href }}" class="group block h-full">
    <div @class([
        'h-full bg-white rounded-2xl border border-slate-100 p-6 flex items-center justify-between',
        'shadow-sm shadow-slate-100/40 transition-all duration-300 hover:-translate-y-1 hover:shadow-lg border-l-4',
        'border-l-sky-500 group-hover:shadow-sky-100/50' => $color === 'sky',
        'border-l-emerald-500 group-hover:shadow-emerald-100/50' => $color === 'emerald',
        'border-l-amber-500 group-hover:shadow-amber-100/50' => $color === 'amber',
        'border-l-rose-500 group-hover:shadow-rose-100/50' => $color === 'rose',
        'border-l-slate-500 group-hover:shadow-slate-100/50' => $color === 'slate',
    ])>
        <div class="min-w-0 flex-1 pr-3">
            <span class="text-xs font-semibold uppercase tracking-wider text-slate-500 block mb-1">{{ $title }}</span>
            <span class="text-2xl font-bold text-slate-900 tracking-tight block">{{ $value }}</span>
        </div>

        <div @class([
            'w-12 h-12 rounded-xl flex items-center justify-center shrink-0 transition-all duration-300 group-hover:scale-110',
            'bg-sky-100 text-sky-700' => $color === 'sky',
            'bg-emerald-100 text-emerald-700' => $color === 'emerald',
            'bg-amber-100 text-amber-700' => $color === 'amber',
            'bg-rose-100 text-rose-700' => $color === 'rose',
            'bg-slate-100 text-slate-700' => $color === 'slate',
        ])>
            @if($slot->isEmpty())
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                </svg>
            @else
                {{ $slot }}
            @endif
        </div>
    </div>
</a>
