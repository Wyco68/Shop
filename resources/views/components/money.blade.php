@props(['amount', 'decimals' => 2])

<span {{ $attributes }}>{{ \App\Support\Money::format($amount, (int) $decimals) }}</span>
