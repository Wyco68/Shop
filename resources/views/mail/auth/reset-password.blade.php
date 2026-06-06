<x-mail::message>
# Reset Your Password

Hi {{ $user->name }},

You are receiving this email because we received a password reset request for your account.

<x-mail::button :url="$url">
Reset Password
</x-mail::button>

This link expires in {{ $expireMinutes }} minutes.

If you did not request a password reset, no further action is required.

Thanks,<br>
{{ config('shop.name', config('app.name')) }}
</x-mail::message>
