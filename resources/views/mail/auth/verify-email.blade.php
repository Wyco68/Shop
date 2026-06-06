<x-mail::message>
# Verify Your Email Address

Hi {{ $user->name }},

Thanks for registering with {{ config('shop.name', config('app.name')) }}. Please confirm your email address by clicking the button below.

This link expires in {{ $expireMinutes }} minutes.

<x-mail::button :url="$url">
Verify Email Address
</x-mail::button>

If you did not create an account, no further action is required.

Thanks,<br>
{{ config('shop.name', config('app.name')) }}
</x-mail::message>
