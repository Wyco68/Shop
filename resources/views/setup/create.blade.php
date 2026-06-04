<x-guest-layout>
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Store setup</h1>
        <p class="text-sm text-gray-600 mt-2">Name your store, choose your base currency (locked after setup), and create the first administrator.</p>
    </div>

    <form method="POST" action="{{ route('setup.store') }}" class="space-y-4">
        @csrf

        <div>
            <x-input-label for="store_name" value="Shop name" />
            <x-text-input id="store_name" class="block mt-1 w-full" type="text" name="store_name" :value="old('store_name', config('shop.name'))" required maxlength="255" />
            <p class="text-xs text-gray-500 mt-1">Shown across the storefront. Set once at install; not editable later in admin.</p>
            <x-input-error :messages="$errors->get('store_name')" class="mt-2" />
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
                <x-input-label for="currency_code" value="Currency code" />
                <x-text-input id="currency_code" class="block mt-1 w-full uppercase" type="text" name="currency_code" :value="old('currency_code', 'USD')" required maxlength="3" pattern="[A-Za-z]{3}" />
                <x-input-error :messages="$errors->get('currency_code')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="currency_symbol" value="Symbol" />
                <x-text-input id="currency_symbol" class="block mt-1 w-full" type="text" name="currency_symbol" :value="old('currency_symbol', '$')" required maxlength="8" />
                <x-input-error :messages="$errors->get('currency_symbol')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="currency_position" value="Symbol position" />
                <select id="currency_position" name="currency_position" class="block mt-1 w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                    <option value="before" @selected(old('currency_position', 'before') === 'before')>Before amount</option>
                    <option value="after" @selected(old('currency_position') === 'after')>After amount</option>
                </select>
                <x-input-error :messages="$errors->get('currency_position')" class="mt-2" />
            </div>
        </div>
        <p class="text-xs text-gray-500 -mt-2">Base currency for all prices and orders. Cannot be changed in admin after setup.</p>

        <div>
            <x-input-label for="name" value="Administrator name (optional)" />
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
