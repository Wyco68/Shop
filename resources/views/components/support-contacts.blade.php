@props(['contacts', 'compact' => false])

@php
    $contactService = app(\App\Services\SupportContactService::class);
@endphp

@if($contacts->isNotEmpty())
<div {{ $attributes->merge(['class' => $compact ? 'flex flex-wrap gap-3 justify-center' : 'grid gap-6 sm:grid-cols-2 lg:grid-cols-3']) }}>
    @foreach($contacts as $contact)
        <div class="{{ $compact ? 'inline-flex items-center gap-2 text-sm' : 'bg-white rounded-xl border border-gray-100 p-5 shadow-sm' }}">
            <span class="{{ $compact ? 'font-medium text-gray-300 capitalize' : 'text-xs font-bold uppercase tracking-wider text-gray-400' }}">
                {{ $contact->type?->label() ?? $contact->type }}
            </span>

            @if($contact->link && $contact->username)
                <a href="{{ $contact->link }}" target="_blank" rel="noopener noreferrer"
                   class="{{ $compact ? 'text-white hover:text-gray-200 underline' : 'block mt-2 text-sky-600 font-medium hover:underline break-all' }}">
                    {{ $contact->username }}
                </a>
            @elseif($contact->username)
                <span class="{{ $compact ? 'text-gray-300' : 'block mt-2 text-gray-700' }}">{{ $contact->username }}</span>
            @endif

            @if($contact->qr_path)
                <img src="{{ $contactService->qrUrl($contact->qr_path) }}" alt="{{ $contact->type?->label() ?? 'Support' }} QR"
                     class="{{ $compact ? 'hidden' : 'mt-4 max-h-40 w-auto rounded-lg border border-gray-100' }}">
            @endif
        </div>
    @endforeach
</div>
@endif
