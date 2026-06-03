<x-guest-layout>
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Store setup</h1>
        <p class="text-sm text-gray-600 mt-2">Create the first administrator account. This page is disabled after an admin exists.</p>
    </div>

    <form method="POST" action="{{ route('setup.store') }}" class="space-y-4">
        @csrf

        <div>
            <x-input-label for="name" value="Name (optional)" />
            <x-text-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name')" autocomplete="name" />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="email" value="Email" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="password" value="Password" />
            <x-text-input id="password" class="block mt-1 w-full" type="password" name="password" required autocomplete="new-password" />
            <p class="text-xs text-gray-500 mt-1">Minimum 12 characters with mixed case, numbers, and symbols.</p>
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="password_confirmation" value="Confirm password" />
            <x-text-input id="password_confirmation" class="block mt-1 w-full" type="password" name="password_confirmation" required autocomplete="new-password" />
        </div>

        <x-primary-button class="w-full justify-center">
            Create administrator
        </x-primary-button>
    </form>
</x-guest-layout>
