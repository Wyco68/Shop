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
                 class="absolute right-0 mt-2 w-52 bg-white border border-slate-100 rounded-xl shadow-lg shadow-slate-100/50 py-1.5 z-50 focus:outline-none"
                 style="display: none;">
                <a href="{{ route('admin.settings.security.edit') }}"
                   class="w-full flex items-center gap-2 px-4 py-2.5 min-h-[44px] text-sm text-slate-700 hover:bg-slate-50 transition-colors">
                    <svg class="w-4.5 h-4.5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                    </svg>
                    Security
                </a>
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
