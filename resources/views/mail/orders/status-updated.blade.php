<x-mail::message>
# Order #{{ $order->id }} Status Updated

Hi {{ $user->name }},

Your order status has changed from **{{ $fromLabel }}** to **{{ $toLabel }}**.

<x-mail::button :url="route('orders.show', $order)">
View Order
</x-mail::button>

Thanks,<br>
{{ config('shop.name', config('app.name')) }}
</x-mail::message>
