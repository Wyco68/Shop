<!-- Sidebar -->
<aside id="admin-sidebar" :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
       class="fixed inset-y-0 left-0 z-50 w-64 bg-slate-900 text-slate-300 flex flex-col border-r border-slate-800 transition-transform duration-300 ease-in-out lg:static lg:h-screen lg:shrink-0">

    <!-- Sidebar Brand -->
    <div class="h-16 px-6 border-b border-slate-800 flex items-center gap-3">
        <img src="{{ $storeLogoUrl ?? asset('images/logo.png') }}" alt="{{ config('shop.name') }}" class="h-8 w-auto filter brightness-0 invert" />
        <div>
            <span class="font-bold text-white tracking-wide text-lg">{{ config('shop.name') }}</span>
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
                    <template x-if="$store.notifications && $store.notifications.unreadCount > 0">
                        <span class="relative flex h-2.5 w-2.5">
                            <span class="absolute inline-flex h-full w-full rounded-full bg-rose-400 opacity-75 animate-ping"></span>
                            <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-rose-500"></span>
                        </span>
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

        <!-- Settings Group -->
        <div>
            <span class="px-3 text-[10px] font-bold text-slate-500 uppercase tracking-widest block mb-3">Settings</span>
            <div class="space-y-1">
                <a href="{{ route('admin.settings.security.edit') }}"
                   class="relative z-10 flex items-center gap-3 px-3 py-3 min-h-[44px] rounded-xl text-sm font-medium transition-all group {{ request()->routeIs('admin.settings.security.*') ? 'bg-sky-500/10 text-sky-400 font-semibold border-l-4 border-sky-400 pl-2' : 'hover:bg-slate-800/60 hover:text-white' }}">
                    <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                    </svg>
                    Security
                </a>
                <a href="{{ route('admin.settings.branding.edit') }}"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all group {{ request()->routeIs('admin.settings.branding.*') ? 'bg-sky-500/10 text-sky-400 font-semibold border-l-4 border-sky-400 pl-2' : 'hover:bg-slate-800/60 hover:text-white' }}">
                    <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    Branding
                </a>
                <a href="{{ route('admin.settings.support.index') }}"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all group {{ request()->routeIs('admin.settings.support.*') ? 'bg-sky-500/10 text-sky-400 font-semibold border-l-4 border-sky-400 pl-2' : 'hover:bg-slate-800/60 hover:text-white' }}">
                    <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                    </svg>
                    Support
                </a>
                <a href="{{ route('admin.settings.payments.index') }}"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all group {{ request()->routeIs('admin.settings.payments.*') ? 'bg-sky-500/10 text-sky-400 font-semibold border-l-4 border-sky-400 pl-2' : 'hover:bg-slate-800/60 hover:text-white' }}">
                    <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                    </svg>
                    Payments
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
                <p class="text-xs text-slate-500 truncate">{{ Auth::user()->email }}</p>
            </div>
        </div>
    </div>
</aside>
