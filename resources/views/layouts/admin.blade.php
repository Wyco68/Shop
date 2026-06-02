<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @if(auth()->check())
        <meta name="user-id" content="{{ auth()->id() }}">
    @endif
    <title>Admin Panel - @yield('title', 'Dashboard')</title>
    <link rel="icon" type="image/png" href="{{ asset('images/logo.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
    <style>
        body { font-family: 'Plus Jakarta Sans', 'Inter', sans-serif; }
    </style>
</head>
<body class="antialiased bg-slate-50 text-slate-800 overflow-x-hidden flex min-h-screen" x-data="{ sidebarOpen: false }">

    <x-demo-banner />

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

    <!-- Sidebar -->
    <aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
           class="fixed inset-y-0 left-0 z-50 w-64 bg-slate-900 text-slate-300 flex flex-col border-r border-slate-800 transition-transform duration-300 ease-in-out lg:static lg:h-screen lg:shrink-0">
        
        <!-- Sidebar Brand -->
        <div class="h-16 px-6 border-b border-slate-800 flex items-center gap-3">
            <img src="{{ asset('images/logo.png') }}" alt="Logo" class="h-8 w-auto filter brightness-0 invert" />
            <div>
                <span class="font-bold text-white tracking-wide text-lg">CarPart</span>
                <span class="text-[10px] block text-sky-400 font-semibold tracking-wider uppercase -mt-1">Admin Console</span>
            </div>
        </div>

        <!-- Sidebar Navigation -->
        <nav class="flex-1 py-6 px-4 space-y-7 overflow-y-auto">
            <!-- Main Group -->
            <div>
                <span class="px-3 text-[10px] font-bold text-slate-500 uppercase tracking-widest block mb-3">Core</span>
                <div class="space-y-1">
                    <a href="{{ route('admin.dashboard') }}" 
                       class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all group {{ request()->routeIs('admin.dashboard') ? 'bg-sky-500/10 text-sky-400 font-semibold border-l-4 border-sky-400 pl-2' : 'hover:bg-slate-800/60 hover:text-white' }}">
                        <svg class="w-5 h-5 shrink-0 {{ request()->routeIs('admin.dashboard') ? 'text-sky-400' : 'text-slate-400 group-hover:text-white transition-colors' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v4a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v4a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v4a2 2 0 01-2 2H6a2 2 0 01-2-2v-4zM14 16a2 2 0 012-2h2a2 2 0 012 2v4a2 2 0 01-2 2h-2a2 2 0 01-2-2v-4z" />
                        </svg>
                        Dashboard
                    </a>
                    
                    <a href="{{ route('admin.notifications.index') }}" 
                       class="w-full flex items-center justify-between px-3 py-2.5 rounded-xl text-sm font-medium transition-all group {{ request()->routeIs('admin.notifications.*') || request()->is('admin/notifications') ? 'bg-sky-500/10 text-sky-400 font-semibold border-l-4 border-sky-400 pl-2' : 'hover:bg-slate-800/60 hover:text-white text-slate-400' }}">
                        <div class="flex items-center gap-3">
                            <svg class="w-5 h-5 shrink-0 transition-colors {{ request()->routeIs('admin.notifications.*') || request()->is('admin/notifications') ? 'text-sky-400' : 'text-slate-400 group-hover:text-white' }}"
                                 fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                            </svg>
                            Notifications
                        </div>
                        <template x-if="$store.notifications.unreadCount > 0">
                            <span x-text="$store.notifications.unreadCount" class="bg-rose-500 text-white text-xs font-bold px-2 py-0.5 rounded-full"></span>
                        </template>
                    </a>
                </div>
            </div>

            <!-- Management Group -->
            <div>
                <span class="px-3 text-[10px] font-bold text-slate-500 uppercase tracking-widest block mb-3">Management</span>
                <div class="space-y-1">
                    <a href="{{ route('admin.products.index') }}" 
                       class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all group {{ request()->routeIs('admin.products.*') ? 'bg-sky-500/10 text-sky-400 font-semibold border-l-4 border-sky-400 pl-2' : 'hover:bg-slate-800/60 hover:text-white' }}">
                        <svg class="w-5 h-5 shrink-0 {{ request()->routeIs('admin.products.*') ? 'text-sky-400' : 'text-slate-400 group-hover:text-white transition-colors' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                        </svg>
                        Products
                    </a>

                    <a href="{{ route('admin.orders.index') }}" 
                       class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all group {{ request()->routeIs('admin.orders.*') ? 'bg-sky-500/10 text-sky-400 font-semibold border-l-4 border-sky-400 pl-2' : 'hover:bg-slate-800/60 hover:text-white' }}">
                        <svg class="w-5 h-5 shrink-0 {{ request()->routeIs('admin.orders.*') ? 'text-sky-400' : 'text-slate-400 group-hover:text-white transition-colors' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                        </svg>
                        Orders
                    </a>

                    <a href="{{ route('admin.categories.index') }}" 
                       class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all group {{ request()->routeIs('admin.categories.*') ? 'bg-sky-500/10 text-sky-400 font-semibold border-l-4 border-sky-400 pl-2' : 'hover:bg-slate-800/60 hover:text-white' }}">
                        <svg class="w-5 h-5 shrink-0 {{ request()->routeIs('admin.categories.*') ? 'text-sky-400' : 'text-slate-400 group-hover:text-white transition-colors' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M7 7h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        Categories
                    </a>

                    <a href="{{ route('admin.users.index') }}" 
                       class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all group {{ request()->routeIs('admin.users.*') ? 'bg-sky-500/10 text-sky-400 font-semibold border-l-4 border-sky-400 pl-2' : 'hover:bg-slate-800/60 hover:text-white' }}">
                        <svg class="w-5 h-5 shrink-0 {{ request()->routeIs('admin.users.*') ? 'text-sky-400' : 'text-slate-400 group-hover:text-white transition-colors' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        Users
                    </a>
                </div>
            </div>

        </nav>

        <!-- Sidebar User Footer -->
        <div class="p-4 border-t border-slate-800 bg-slate-950/40">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-sky-500 flex items-center justify-center text-white font-bold text-sm shrink-0 shadow-inner">
                    {{ substr(Auth::user()->name ?? 'A', 0, 1) }}
                </div>
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-semibold text-white truncate">{{ Auth::user()->name ?? 'Administrator' }}</p>
                    <p class="text-xs text-slate-500 truncate">{{ Auth::user()->email ?? 'admin@carpart.com' }}</p>
                </div>
            </div>
        </div>
    </aside>

    <!-- Main Workspace -->
    <div class="flex-1 flex flex-col min-w-0 overflow-y-auto h-screen relative">
        
        <!-- Sticky Topbar -->
        <header class="h-16 sticky top-0 z-30 bg-white/80 backdrop-blur-md border-b border-slate-100 flex items-center justify-between px-6 shrink-0 shadow-sm shadow-slate-100/40">
            <!-- Left Side: Mobile toggle & Breadcrumb -->
            <div class="flex items-center gap-4">
                <button @click="sidebarOpen = true" 
                        class="p-2 -ml-2 rounded-xl text-slate-500 hover:bg-slate-100 transition-colors lg:hidden focus:outline-none focus:ring-2 focus:ring-sky-500/20"
                        aria-label="Open sidebar">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>

                <nav class="flex items-center gap-2 text-sm" aria-label="Breadcrumb">
                    <span class="text-slate-400">Admin</span>
                    <svg class="w-3.5 h-3.5 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                    </svg>
                    <span class="font-semibold text-slate-800">@yield('title', 'Dashboard')</span>
                </nav>
            </div>

            <!-- Right Side: Action & Profile Menu -->
            <div class="flex items-center gap-4" x-data="{ userMenuOpen: false }">
                <!-- User Dropdown Menu -->
                <div class="relative">
                    <button @click="userMenuOpen = !userMenuOpen" 
                            @click.away="userMenuOpen = false"
                            class="flex items-center gap-2 p-1.5 pr-3 rounded-xl hover:bg-slate-50 transition-all focus:outline-none focus:ring-2 focus:ring-sky-500/20">
                        <div class="w-8 h-8 rounded-lg bg-sky-500 flex items-center justify-center text-white font-bold text-sm shrink-0">
                            {{ substr(Auth::user()->name ?? 'A', 0, 1) }}
                        </div>
                        <span class="text-sm font-medium text-slate-700 hidden sm:inline-block max-w-[120px] truncate">{{ Auth::user()->name ?? 'Administrator' }}</span>
                        <svg class="w-4 h-4 text-slate-400 transition-transform duration-200" :class="userMenuOpen ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>

                    <!-- Dropdown Panel -->
                    <div x-show="userMenuOpen"
                         x-transition:enter="transition ease-out duration-100"
                         x-transition:enter-start="transform opacity-0 scale-95"
                         x-transition:enter-end="transform opacity-100 scale-100"
                         x-transition:leave="transition ease-in duration-75"
                         x-transition:leave-start="transform opacity-100 scale-100"
                         x-transition:leave-end="transform opacity-0 scale-95"
                         class="absolute right-0 mt-2 w-48 bg-white border border-slate-100 rounded-xl shadow-lg shadow-slate-100/50 py-1.5 z-50 focus:outline-none"
                         style="display: none;">
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="w-full flex items-center gap-2 px-4 py-2 text-sm text-rose-600 hover:bg-rose-50/50 transition-colors text-left">
                                <svg class="w-4.5 h-4.5 text-rose-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                </svg>
                                Log Out
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </header>

        <!-- Flash Messages (Alpine Auto dismiss) -->
        <div class="fixed top-20 right-6 z-50 space-y-3">
            @if(session('success'))
                <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)"
                     x-transition:enter="transition ease-out duration-300"
                     x-transition:enter-start="transform translate-y-2 opacity-0"
                     x-transition:enter-end="transform translate-y-0 opacity-100"
                     x-transition:leave="transition ease-in duration-200"
                     x-transition:leave-start="transform translate-y-0 opacity-100"
                     x-transition:leave-end="transform translate-y-2 opacity-0"
                     class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-5 py-3 rounded-xl shadow-lg shadow-emerald-100/30 text-sm flex items-center gap-3">
                    <div class="w-6 h-6 rounded-lg bg-emerald-500 flex items-center justify-center text-white shrink-0 shadow-sm">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                    </div>
                    <div>
                        <p class="font-semibold text-emerald-950">Success</p>
                        <p class="text-emerald-700/90 text-xs mt-0.5">{{ session('success') }}</p>
                    </div>
                </div>
            @endif
            @if(session('error'))
                <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)"
                     x-transition:enter="transition ease-out duration-300"
                     x-transition:enter-start="transform translate-y-2 opacity-0"
                     x-transition:enter-end="transform translate-y-0 opacity-100"
                     x-transition:leave="transition ease-in duration-200"
                     x-transition:leave-start="transform translate-y-0 opacity-100"
                     x-transition:leave-end="transform translate-y-2 opacity-0"
                     class="bg-rose-50 border border-rose-200 text-rose-800 px-5 py-3 rounded-xl shadow-lg shadow-rose-100/30 text-sm flex items-center gap-3">
                    <div class="w-6 h-6 rounded-lg bg-rose-500 flex items-center justify-center text-white shrink-0 shadow-sm">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm-1-9a1 1 0 012 0v4a1 1 0 01-2 0V9zm0 6a1 1 0 112 0 1 1 0 01-2 0z" clip-rule="evenodd"/></svg>
                    </div>
                    <div>
                        <p class="font-semibold text-rose-950">Error</p>
                        <p class="text-rose-700/90 text-xs mt-0.5">{{ session('error') }}</p>
                    </div>
                </div>
            @endif
        </div>

        <!-- Main Content Area -->
        <main class="flex-1 p-6 md:p-8 min-w-0">
            @yield('content')
        </main>
        
        <!-- Sticky Bottom Copyright -->
        <footer class="py-4 border-t border-slate-100 px-6 text-center text-xs text-slate-400 bg-white shrink-0">
            <p>&copy; {{ date('Y') }} CarPart Admin Console. All rights reserved.</p>
        </footer>
    </div>

    <!-- AlpineJS for interactive elements -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    @stack('scripts')
</body>
</html>
