<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <title>{{ config('shop.name') }} - @yield('title', 'Home')</title>
    @include('layouts.partials.head-meta')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="antialiased bg-[#fbfbfd] text-[#1d1d1f] overflow-x-hidden flex flex-col min-h-screen">

    @include('layouts.navigation')

    <x-flash-messages />

    <main class="flex-1 min-w-0 w-full">
        @yield('content')
    </main>

    <footer class="bg-gray-900 text-gray-400 py-10 mt-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center text-sm space-y-4">
            <p class="font-semibold text-white mb-1">{{ config('shop.name') }}</p>
            @if(isset($supportContacts) && $supportContacts->isNotEmpty())
                <x-support-contacts :contacts="$supportContacts" compact class="mb-2" />
            @endif
            <p>
                <a href="{{ route('contact') }}" class="text-gray-300 hover:text-white underline">Contact</a>
                &middot;
                &copy; {{ date('Y') }} {{ config('shop.name') }}. All rights reserved.
            </p>
        </div>
    </footer>

    @stack('scripts')
</body>
</html>

