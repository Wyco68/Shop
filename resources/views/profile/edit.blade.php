@extends('layouts.app')
@section('title', 'My Profile')
@section('content')

<div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    <h1 class="text-2xl font-bold text-gray-900 mb-8">My Profile</h1>

    @if(session('status') === 'profile-updated')
        <div class="bg-green-50 border border-green-200 text-green-800 rounded-xl px-5 py-3 mb-6 text-sm flex items-center gap-2">
            <svg class="w-4 h-4 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
            Profile updated successfully.
        </div>
    @endif

    {{-- Profile Information --}}
    <div class="bg-white rounded-xl border border-gray-100 p-6 mb-6">
        <h2 class="text-base font-semibold text-gray-900 mb-5">Account Information</h2>
        <form method="POST" action="{{ route('profile.update') }}" class="space-y-4">
            @csrf
            @method('PATCH')

            {{-- Name --}}
            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Full Name</label>
                <input type="text" id="name" name="name"
                    value="{{ old('name', $user->name) }}" required autofocus
                    class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gray-400 @error('name') border-red-400 @enderror">
                @error('name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            {{-- Email --}}
            <div>
                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
                <input type="email" id="email" name="email"
                    value="{{ old('email', $user->email) }}" required
                    class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gray-400 @error('email') border-red-400 @enderror">
                @error('email') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                @if($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && !$user->hasVerifiedEmail())
                    <p class="text-xs text-yellow-600 mt-1">
                        Your email is unverified.
                        <form method="POST" action="{{ route('verification.send') }}" class="inline">
                            @csrf
                            <button type="submit" class="underline hover:text-yellow-800">Resend verification</button>
                        </form>
                    </p>
                @endif
            </div>

            {{-- Phone --}}
            <div>
                <label for="phone_num" class="block text-sm font-medium text-gray-700 mb-1">
                    Phone Number <span class="text-gray-400 font-normal">(optional)</span>
                </label>
                <input type="text" id="phone_num" name="phone_num"
                    value="{{ old('phone_num', $user->phone_num) }}"
                    placeholder="e.g. 555-1234"
                    class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gray-400 @error('phone_num') border-red-400 @enderror">
                @error('phone_num') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            {{-- Address --}}
            <div>
                <label for="address" class="block text-sm font-medium text-gray-700 mb-1">
                    Address <span class="text-gray-400 font-normal">(optional)</span>
                </label>
                <input type="text" id="address" name="address"
                    value="{{ old('address', $user->address) }}"
                    placeholder="Your shipping address"
                    class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gray-400 @error('address') border-red-400 @enderror">
                @error('address') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <button type="submit"
                class="w-full bg-gray-900 text-white font-semibold py-2.5 rounded-xl hover:bg-gray-700 transition text-sm">
                Save Changes
            </button>
        </form>
    </div>

    {{-- Logout --}}
    <div class="bg-white rounded-xl border border-gray-100 p-6 mb-6">
        <h2 class="text-base font-semibold text-gray-900 mb-2">Sign Out</h2>
        <p class="text-sm text-gray-500 mb-4">Log out of your account on this device.</p>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit"
                class="border border-red-200 text-red-600 bg-red-50 text-sm font-medium px-5 py-2 rounded-lg hover:bg-red-100 transition">
                Log Out
            </button>
        </form>
    </div>

    @if($user->isAdmin())
    <div class="bg-amber-50 border border-amber-200 rounded-xl p-5 mb-6 text-sm text-amber-900">
        <p class="font-semibold mb-1">Administrator account</p>
        <p>Password changes are CLI-only for security: <code class="bg-amber-100 px-1 rounded text-xs">php artisan admin:change-password</code></p>
    </div>
    @else
    {{-- Change Password --}}
    <div class="bg-white rounded-xl border border-gray-100 p-6 mb-6">
        <h2 class="text-base font-semibold text-gray-900 mb-5">Change Password</h2>
        <form method="POST" action="{{ route('profile.update') }}" class="space-y-4">
            @csrf
            @method('PATCH')
            {{-- Keep existing email/name intact --}}
            <input type="hidden" name="name" value="{{ $user->name }}">
            <input type="hidden" name="email" value="{{ $user->email }}">

            <div>
                <label for="password" class="block text-sm font-medium text-gray-700 mb-1">New Password</label>
                <input type="password" id="password" name="password"
                    class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gray-400">
                @error('password') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-1">Confirm New Password</label>
                <input type="password" id="password_confirmation" name="password_confirmation"
                    class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gray-400">
            </div>

            <button type="submit"
                class="w-full border border-gray-900 text-gray-900 font-semibold py-2.5 rounded-xl hover:bg-gray-900 hover:text-white transition text-sm">
                Update Password
            </button>
        </form>
    </div>
    @endif

    {{-- Danger Zone --}}
    <div class="bg-white rounded-xl border border-red-100 p-6" x-data="{ open: false }">
        <h2 class="text-base font-semibold text-red-700 mb-2">Danger Zone</h2>
        <p class="text-sm text-gray-500 mb-4">Permanently delete your account and all associated data.</p>
        <button type="button" @click="open = true"
            class="border border-red-400 text-red-500 text-sm font-medium px-5 py-2 rounded-lg hover:bg-red-50 transition">
            Delete Account
        </button>

        {{-- Delete Confirmation Modal --}}
        <div x-show="open" x-transition class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 px-4" style="display:none">
            <div class="bg-white rounded-2xl p-6 w-full max-w-sm shadow-xl" @click.stop>
                <h3 class="text-lg font-semibold text-gray-900 mb-2">Delete your account?</h3>
                <p class="text-sm text-gray-500 mb-4">This action cannot be undone. Enter your password to confirm.</p>
                <form method="POST" action="{{ route('profile.destroy') }}" class="space-y-3">
                    @csrf
                    @method('DELETE')
                    <input type="password" name="password" placeholder="Your password" required
                        class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-red-400">
                    @error('password', 'userDeletion') <p class="text-red-500 text-xs">{{ $message }}</p> @enderror
                    <div class="flex gap-3 pt-1">
                        <button type="button" @click="open = false"
                            class="flex-1 border border-gray-200 text-gray-700 py-2 rounded-lg text-sm hover:bg-gray-50 transition">
                            Cancel
                        </button>
                        <button type="submit"
                            class="flex-1 bg-red-600 text-white py-2 rounded-lg text-sm font-medium hover:bg-red-700 transition">
                            Delete
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

</div>

@endsection
