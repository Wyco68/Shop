<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <title>Admin Panel - @yield('title', 'Dashboard')</title>
    @include('layouts.partials.head-meta')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', 'Inter', sans-serif; }
        #admin-sidebar { view-transition-name: admin-sidebar; }
        #admin-topbar { view-transition-name: admin-topbar; }
    </style>
</head>
<body class="antialiased bg-slate-50 text-slate-800 overflow-x-hidden flex min-h-screen" x-data="{ sidebarOpen: false }">

    <!-- Mobile Sidebar Backdrop -->
    <div x-show="sidebarOpen" 
         x-transition:enter="transition-opacity ease-linear duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition-opacity ease-linear duration-300"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-40 bg-slate-900/60 backdrop-blur-sm lg:hidden" 
         @click="sidebarOpen = false"></div>

    <x-admin.sidebar />

    <!-- Main Workspace -->
    <div class="flex-1 flex flex-col min-w-0 overflow-y-auto h-screen relative">

        @isset($demoAdminSession)
            <div
                x-data="{
                    expiresAt: {{ $demoAdminSession->expires_at->getTimestamp() }} * 1000,
                    remaining: '',
                    tick() {
                        const ms = this.expiresAt - Date.now();
                        if (ms <= 0) { this.remaining = '0:00'; return; }
                        const m = Math.floor(ms / 60000);
                        const s = Math.floor((ms % 60000) / 1000).toString().padStart(2, '0');
                        this.remaining = `${m}:${s}`;
                    }
                }"
                x-init="tick(); setInterval(() => tick(), 1000)"
                class="w-full bg-amber-400 text-amber-950 text-sm font-medium px-4 py-2 flex flex-wrap items-center justify-center gap-x-2 gap-y-1 text-center"
            >
                <span>Demo mode — you're using shared test credentials.</span>
                <span>Every change is undone automatically when you log out or the session ends.</span>
                <span>Time left: <span x-text="remaining" class="font-semibold tabular-nums"></span></span>
            </div>
        @endisset

        <x-admin.topbar />

        <x-admin.flash-messages />

        <!-- Main Content Area -->
        <main class="flex-1 p-6 md:p-8 min-w-0">
            @yield('content')
        </main>
        
        <!-- Sticky Bottom Copyright -->
        <footer class="py-4 border-t border-slate-100 px-6 text-center text-xs text-slate-400 bg-white shrink-0">
            <p>&copy; {{ date('Y') }} {{ config('shop.name') }} Admin. All rights reserved.</p>
        </footer>
    </div>

    @stack('scripts')
</body>
</html>
