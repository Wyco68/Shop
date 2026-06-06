@extends('layouts.admin')

@section('title', 'Security')

@section('content')
<div class="max-w-3xl mx-auto space-y-8">
    <div class="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm">
        <h1 class="text-2xl font-bold text-slate-800">Account security</h1>
        <p class="text-sm text-slate-500 mt-1">Change your administrator password with strict verification.</p>
    </div>

    @if (session('status') === 'admin-password-updated')
        <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-xl px-5 py-3 text-sm">
            Password updated. Other sessions were signed out. A confirmation email was sent.
        </div>
    @endif

    <x-admin.card title="Change password">
        @if (! $webChangeEnabled)
            <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                Web password changes are disabled. Use:
                <code class="bg-amber-100 px-1 rounded text-xs">php artisan admin:change-password</code>
            </div>
        @elseif ($cooldownEndsAt)
            <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                Password was changed recently. For security, you must wait until
                <strong>{{ $cooldownEndsAt->timezone(config('app.timezone'))->format('M j, Y g:i A') }}</strong>
                before changing it again ({{ $cooldownMinutes }}-minute cooldown).
            </div>
        @else
            <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600 mb-6">
                <ul class="list-disc list-inside space-y-1">
                    <li>Current password is required to save changes</li>
                    <li>Minimum 12 characters with uppercase, lowercase, number, and symbol</li>
                    <li>Password must not appear in known data breaches</li>
                    <li>All other active sessions will be signed out</li>
                    <li>Changes are limited to once every {{ $cooldownMinutes }} minutes</li>
                </ul>
            </div>

            <form method="POST" action="{{ route('admin.settings.security.password.update') }}" class="space-y-5">
                @csrf
                @method('PUT')

                <div>
                    <label for="current_password" class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Current password</label>
                    <input type="password" id="current_password" name="current_password" required autocomplete="current-password"
                           class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-sky-500 @error('current_password') border-rose-400 @enderror">
                    @error('current_password')<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="password" class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">New password</label>
                    <input type="password" id="password" name="password" required autocomplete="new-password"
                           class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-sky-500 @error('password') border-rose-400 @enderror">
                    @error('password')<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="password_confirmation" class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Confirm new password</label>
                    <input type="password" id="password_confirmation" name="password_confirmation" required autocomplete="new-password"
                           class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-sky-500">
                </div>

                <button type="submit"
                        class="px-6 py-2.5 bg-sky-600 hover:bg-sky-700 text-white rounded-xl font-semibold text-sm transition">
                    Update administrator password
                </button>
            </form>
        @endif
    </x-admin.card>

    <x-admin.card title="Forgot password?">
        @if (! $passwordResetEnabled)
            <p class="text-sm text-amber-800 bg-amber-50 border border-amber-200 rounded-xl px-4 py-3">
                Email password reset is disabled for administrators. Use this Security page or
                <code class="bg-amber-100 px-1 rounded text-xs">php artisan admin:change-password</code>.
            </p>
        @elseif (! $passwordResetAllowed && $cooldownEndsAt)
            <p class="text-sm text-amber-800 bg-amber-50 border border-amber-200 rounded-xl px-4 py-3">
                Email reset is temporarily disabled because your password was changed recently.
                Available again after
                <strong>{{ $cooldownEndsAt->timezone(config('app.timezone'))->format('M j, Y g:i A') }}</strong>.
            </p>
        @else
            <p class="text-sm text-slate-600 mb-4">
                If you are locked out, use the standard reset flow. A secure link will be emailed to your administrator address.
            </p>
            <a href="{{ route('password.request') }}"
               class="inline-flex items-center text-sm font-semibold text-sky-600 hover:text-sky-700 hover:underline">
                Request password reset email
            </a>
        @endif
    </x-admin.card>
</div>
@endsection
