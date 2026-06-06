<x-guest-layout>
    <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100 text-center mb-6">Check Your Email</h2>

    <div class="mb-4 text-sm text-gray-600 dark:text-gray-400">
        Thanks for registering! We sent a verification link to
        <span class="font-medium text-gray-900 dark:text-gray-100">{{ $email }}</span>.
        Please click the link in that email to activate your account before logging in.
    </div>

    @if (session('status') == 'verification-link-sent')
        <div class="mb-4 font-medium text-sm text-green-600 dark:text-green-400">
            A new verification link has been sent to your email address.
        </div>
    @endif

    <form method="POST" action="{{ route('verification.resend') }}" class="mt-6">
        @csrf
        <input type="hidden" name="email" value="{{ $email }}">

        <x-primary-button class="w-full justify-center">
            Resend Verification Email
        </x-primary-button>
    </form>

    <p class="mt-4 text-center text-sm text-gray-600 dark:text-gray-400">
        Already verified?
        <a href="{{ route('login') }}" class="text-indigo-600 hover:underline font-medium">Log in</a>
    </p>
</x-guest-layout>
