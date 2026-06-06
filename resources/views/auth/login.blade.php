<x-guest-layout>
    <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100 text-center mb-6">Login to Your Account</h2>

    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    @if ($errors->getBag('unverified')->isNotEmpty())
        <div class="mb-4 p-4 rounded-md bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800">
            <p class="text-sm text-amber-800 dark:text-amber-200">
                {{ $errors->getBag('unverified')->first('email') }}
            </p>

            <form method="POST" action="{{ route('verification.resend') }}" class="mt-3">
                @csrf
                <input type="hidden" name="email" value="{{ old('email') }}">

                <button type="submit" class="text-sm font-medium text-indigo-600 hover:underline">
                    Resend verification email
                </button>
            </form>
        </div>
    @endif

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div class="mt-4">
            <x-input-label for="password" :value="__('Password')" />
            <x-text-input id="password" class="block mt-1 w-full" type="password" name="password" required autocomplete="current-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div class="flex items-center justify-end mt-2">
            <a href="{{ route('password.request') }}" class="text-sm text-indigo-600 hover:underline">
                Forgot your password?
            </a>
        </div>

        <div class="mt-6">
            <x-primary-button class="w-full justify-center">
                {{ __('Login') }}
            </x-primary-button>
        </div>

        <p class="mt-4 text-center text-sm text-gray-600 dark:text-gray-400">
            Don't have an account?
            <a href="{{ route('register') }}" class="text-indigo-600 hover:underline font-medium">Register</a>
        </p>
    </form>
</x-guest-layout>
