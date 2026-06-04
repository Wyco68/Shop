@extends('layouts.app')

@section('title', 'Contact')

@section('content')
<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <h1 class="text-3xl font-bold text-gray-900 mb-2">Contact support</h1>
    <p class="text-gray-500 mb-10">Reach us on your preferred channel.</p>

    @if($supportContacts->isEmpty())
        <p class="text-gray-500 text-sm">Support channels are not configured yet. Please check back later.</p>
    @else
        <x-support-contacts :contacts="$supportContacts" />
    @endif
</div>
@endsection
