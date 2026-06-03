<div class="bg-white rounded-xl border border-gray-100 p-6 sticky top-24">
    <h2 class="font-semibold text-gray-900 mb-4">Your Items</h2>
    <div class="space-y-3 divide-y divide-gray-50">
        @foreach($summary['items'] as $item)
        <div class="flex justify-between items-start pt-3 first:pt-0 text-sm">
            <div class="flex-1 min-w-0 pr-3">
                <p class="font-medium text-gray-900 line-clamp-1">{{ $item['product']->name }}</p>
                <p class="text-xs text-gray-400">× {{ $item['quantity'] }}</p>
            </div>
            <span class="font-semibold text-gray-900 shrink-0"><x-money :amount="$item['final_line_total']" /></span>
        </div>
        @endforeach
    </div>
    <div class="mt-4 pt-4 border-t border-gray-100 space-y-2 text-sm">
        <div class="flex justify-between text-gray-500">
            <span>Subtotal</span>
            <span><x-money :amount="$summary['subtotal']" /></span>
        </div>
        @if($summary['discount_total'] > 0)
        <div class="flex justify-between text-red-500">
            <span>Discount</span>
            <span>− <x-money :amount="$summary['discount_total']" /></span>
        </div>
        @endif
        <div class="flex justify-between font-bold text-gray-900 text-base border-t border-gray-100 pt-2">
            <span>Total</span>
            <span><x-money :amount="$summary['total']" /></span>
        </div>
    </div>
    <a href="{{ route('cart.index') }}"
       class="block mt-4 text-center text-sm text-gray-400 hover:text-gray-600 transition">
        ← Edit Cart
    </a>
</div>
