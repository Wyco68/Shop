<nav x-data="{ open: false, logout() { fetch('/logout', { method: 'POST', headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Accept': 'application/json' } }).then(() => { if (Alpine.store('notifications')) Alpine.store('notifications').clear(); window.location.href = '/login'; }) } }" class="sticky top-0 z-40 bg-white/80 backdrop-blur-md border-b border-gray-100/50">
    <!-- Primary Navigation Menu -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <!-- Logo -->
                <div class="shrink-0 flex items-center">
                    <a href="{{ route('home') }}">
                        <img src="{{ asset('images/logo.png') }}" alt="Logo" class="block h-9 w-auto" />
                    </a>
                </div>

                <!-- Navigation Links -->
                <div class="hidden space-x-6 sm:-my-px sm:ms-8 sm:flex">
                    @if(!Auth::check() || Auth::user()->role !== 'admin')
                        <x-nav-link :href="route('home')" :active="request()->routeIs('home')" class="text-sm tracking-wide {{ request()->routeIs('home') ? 'text-black' : 'text-gray-500 hover:text-black transition-colors' }}">
                            {{ __('Home') }}
                        </x-nav-link>
                        <x-nav-link :href="route('products.index')" :active="request()->routeIs('products.*')" class="text-sm tracking-wide {{ request()->routeIs('products.*') ? 'text-black' : 'text-gray-500 hover:text-black transition-colors' }}">
                            {{ __('Products') }}
                        </x-nav-link>
                    @endif
                    @auth
                        @if(Auth::user()->role === 'admin')
                            <x-nav-link :href="route('admin.dashboard')" :active="request()->routeIs('admin.dashboard')" class="text-sm tracking-wide {{ request()->routeIs('admin.dashboard') ? 'text-black font-bold' : 'text-gray-500 hover:text-black transition-colors' }}">
                                {{ __('Dashboard') }}
                            </x-nav-link>
                            <x-nav-link :href="route('admin.products.index')" :active="request()->routeIs('admin.products.*')" class="text-sm tracking-wide {{ request()->routeIs('admin.products.*') ? 'text-black font-bold' : 'text-gray-500 hover:text-black transition-colors' }}">
                                {{ __('Products') }}
                            </x-nav-link>
                            <x-nav-link :href="route('admin.orders.index')" :active="request()->routeIs('admin.orders.*')" class="text-sm tracking-wide {{ request()->routeIs('admin.orders.*') ? 'text-black font-bold' : 'text-gray-500 hover:text-black transition-colors' }}">
                                {{ __('Orders') }}
                            </x-nav-link>

                            <x-nav-link :href="route('admin.categories.index')" :active="request()->routeIs('admin.categories.*')" class="text-sm tracking-wide {{ request()->routeIs('admin.categories.*') ? 'text-black font-bold' : 'text-gray-500 hover:text-black transition-colors' }}">
                                {{ __('Categories') }}
                            </x-nav-link>
                        @else
                            <x-nav-link :href="route('cart.index')" :active="request()->routeIs('cart.*')" class="text-sm tracking-wide {{ request()->routeIs('cart.*') ? 'text-black' : 'text-gray-500 hover:text-black transition-colors' }}">
                                {{ __('Cart') }}
                            </x-nav-link>
                            <x-nav-link :href="route('orders.index')" :active="request()->routeIs('orders.*')" class="text-sm tracking-wide {{ request()->routeIs('orders.*') ? 'text-black' : 'text-gray-500 hover:text-black transition-colors' }}">
                                {{ __('Orders') }}
                            </x-nav-link>
                        @endif
                    @endauth
                </div>
            </div>

            @auth
                <!-- Actions & Account -->
                <div class="hidden sm:flex sm:items-center sm:ms-6 space-x-4">
                    <!-- Notification Bell -->
                    <a href="{{ route('notifications.index') }}" class="relative p-2 text-gray-500 hover:text-black transition-colors focus:outline-none">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                        </svg>
                        <template x-if="$store.notifications && $store.notifications.unreadCount > 0">
                            <span x-text="$store.notifications.unreadCount" class="absolute top-1 right-1 flex items-center justify-center w-4 h-4 text-[10px] font-bold text-white bg-red-500 rounded-full"></span>
                        </template>
                    </a>

                    <!-- Account Button -->
                    <a href="{{ route('profile.edit') }}"
                        class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-black bg-white hover:text-gray-700 focus:outline-none transition ease-in-out duration-150 {{ request()->routeIs('profile.*') ? 'font-bold' : '' }}">
                        {{ Auth::user()->name }}
                    </a>
                </div>
            @else
                <!-- Guest Links -->
                <div class="hidden sm:flex sm:items-center sm:ms-6 space-x-4">
                    <a href="{{ route('login') }}"
                        class="text-sm text-black hover:text-gray-700">Log
                        in</a>
                    <a href="{{ route('register') }}"
                        class="text-sm text-black hover:text-gray-700">Register</a>
                </div>
            @endauth

            <!-- Hamburger -->
            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = ! open"
                    class="inline-flex items-center justify-center p-2 rounded-md text-gray-600 hover:text-black hover:bg-gray-100 focus:outline-none focus:bg-gray-100 focus:text-black transition duration-150 ease-in-out">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex"
                            stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round"
                            stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Responsive Navigation Menu -->
    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden">
        <div class="pt-2 pb-3 space-y-1">
            @if(!Auth::check() || Auth::user()->role !== 'admin')
                <x-responsive-nav-link :href="route('home')" :active="request()->routeIs('home')">
                    {{ __('Home') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('products.index')" :active="request()->routeIs('products.*')">
                    {{ __('Products') }}
                </x-responsive-nav-link>
            @endif
            @auth
                @if(Auth::user()->role === 'admin')
                    <x-responsive-nav-link :href="route('admin.dashboard')" :active="request()->routeIs('admin.dashboard')">
                        {{ __('Dashboard') }}
                    </x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('admin.products.index')" :active="request()->routeIs('admin.products.*')">
                        {{ __('Products') }}
                    </x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('admin.orders.index')" :active="request()->routeIs('admin.orders.*')">
                        {{ __('Orders') }}
                    </x-responsive-nav-link>

                    <x-responsive-nav-link :href="route('admin.categories.index')" :active="request()->routeIs('admin.categories.*')">
                        {{ __('Categories') }}
                    </x-responsive-nav-link>
                @else
                    <x-responsive-nav-link :href="route('cart.index')" :active="request()->routeIs('cart.*')">
                        {{ __('Cart') }}
                    </x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('orders.index')" :active="request()->routeIs('orders.*')">
                        {{ __('Orders') }}
                    </x-responsive-nav-link>
                @endif
            @endauth
        </div>

        @auth
            <!-- Responsive Settings Options -->
            <div class="pt-4 pb-1 border-t border-gray-200">
                <div class="px-4">
                    <div class="font-medium text-base text-black">{{ Auth::user()->name }}</div>
                    <div class="font-medium text-sm text-gray-600">{{ Auth::user()->email }}</div>
                </div>

                <div class="mt-3 space-y-1">
                    <!-- Additional responsive links can go here -->
                </div>
            </div>
        @else
            <div class="pt-4 pb-1 border-t border-gray-200 dark:border-gray-600">
                <div class="mt-3 space-y-1">
                    <x-responsive-nav-link :href="route('login')">
                        {{ __('Log in') }}
                    </x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('register')">
                        {{ __('Register') }}
                    </x-responsive-nav-link>
                </div>
            </div>
        @endauth
    </div>
</nav>