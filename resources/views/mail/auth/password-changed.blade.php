<x-mail::message>
# Password Changed

Hi {{ $user->name }},

Your account password was changed successfully.

If you did not make this change, contact support immediately.

Thanks,<br>
{{ config('shop.name', config('app.name')) }}
</x-mail::message>
