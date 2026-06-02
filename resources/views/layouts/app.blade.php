<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @if(auth()->check())
        <meta name="user-id" content="{{ auth()->id() }}">
    @endif
    <title>CarPart - @yield('title', 'Home')</title>
    <link rel="icon" type="image/png" href="{{ asset('images/logo.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="antialiased bg-[#fbfbfd] text-[#1d1d1f] overflow-x-hidden flex flex-col min-h-screen">

    @include('layouts.navigation')

    {{-- Flash Messages --}}
    @if(session('success'))
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)"
             class="fixed top-4 right-4 z-50 bg-green-100 border border-green-400 text-green-800 px-5 py-3 rounded-lg shadow-md text-sm flex items-center gap-2">
            <svg class="w-4 h-4 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)"
             class="fixed top-4 right-4 z-50 bg-red-100 border border-red-400 text-red-800 px-5 py-3 rounded-lg shadow-md text-sm flex items-center gap-2">
            <svg class="w-4 h-4 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm-1-9a1 1 0 012 0v4a1 1 0 01-2 0V9zm0 6a1 1 0 112 0 1 1 0 01-2 0z" clip-rule="evenodd"/></svg>
            {{ session('error') }}
        </div>
    @endif

    <main class="flex-1 min-w-0 w-full">
        @yield('content')
    </main>

    <footer class="bg-gray-900 text-gray-400 py-10 mt-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center text-sm">
            <p class="font-semibold text-white mb-1">CarPart</p>
            <p>&copy; {{ date('Y') }} CarPart. All rights reserved.</p>
        </div>
    </footer>

    @stack('scripts')
</body>
</html>

