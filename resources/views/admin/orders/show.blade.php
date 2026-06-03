@extends('layouts.admin')

@section('title', 'Order #' . $order->id)

@section('content')
<div class="space-y-8" x-data="orderManager({
    orderId: {{ $order->id }},
    status: '{{ $order->status }}',
    hasProof: {{ $order->latestPayment && $order->latestPayment->proof_path ? 'true' : 'false' }},
    paymentStatus: '{{ $order->latestPayment->status ?? 'none' }}'
})">

    <!-- WarningBox Component (Alpine) -->
    <template x-teleport="body">
        <div x-show="warning.show" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 backdrop-blur-sm" style="display: none;" x-transition>
            <div class="bg-white rounded-2xl shadow-xl max-w-sm w-full mx-4 p-6" @click.away="warning.show = false">
                <h3 class="text-lg font-bold text-slate-900 mb-2" x-text="warning.title"></h3>
                <p class="text-slate-600 mb-6 text-sm leading-relaxed" x-text="warning.message"></p>
                <div class="flex justify-end gap-3">
                    <button @click="warning.show = false" class="px-4 py-2 rounded-xl text-sm font-medium text-slate-600 hover:bg-slate-50 transition">Cancel</button>
                    <button @click="confirmAction()" x-show="warning.actionUrl" class="px-4 py-2 rounded-xl text-sm font-bold text-white bg-slate-900 hover:bg-slate-800 transition shadow-sm" x-text="warning.confirmText"></button>
                    <button @click="warning.show = false" x-show="!warning.actionUrl" class="px-4 py-2 rounded-xl text-sm font-bold text-white bg-slate-900 hover:bg-slate-800 transition shadow-sm" x-text="warning.confirmText"></button>
                </div>
            </div>
        </div>
    </template>
    
    <!-- Top Action bar -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-white p-6 rounded-2xl border border-slate-100 shadow-sm shadow-slate-100/40">
        <div class="flex flex-wrap items-center gap-3">
            <div>
                <h1 class="text-2xl font-bold text-slate-800 tracking-tight">Order #{{ $order->id }}</h1>
                <p class="text-sm text-slate-500 mt-1">Placed on {{ $order->created_at->format('M d, Y H:i') }}</p>
            </div>
            <div class="px-3 py-1 text-xs font-bold uppercase tracking-wider rounded-full border border-slate-200/50" 
                 :class="{
                    'bg-amber-50 text-amber-600': status === 'pending_payment' || status === 'pending',
                    'bg-emerald-50 text-emerald-600': status === 'paid' || status === 'completed',
                    'bg-sky-50 text-sky-600': status === 'processing' || status === 'shipped',
                    'bg-rose-50 text-rose-600': status === 'cancelled' || status === 'refunded'
                 }"
                 x-text="status.replace('_', ' ')">
            </div>
        </div>
        <div class="shrink-0">
            <a href="{{ route('admin.orders.index') }}" class="inline-flex items-center gap-2 px-4 py-2 border border-slate-200 text-slate-600 hover:text-slate-800 hover:bg-slate-50 rounded-xl font-medium text-sm transition">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Back to Orders
            </a>
        </div>
    </div>

    <!-- Main 3-Column Responsive Grid (2-span for details, 1-span for side sidebar widgets) -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        
        <!-- Left: Items list & Notes (Spans 2 columns) -->
        <div class="lg:col-span-2 space-y-8">
            
            <!-- Order Items Card -->
            <x-admin.card title="Order Line Items">
                <x-admin.data-table class="w-full">
                    <thead>
                        <tr class="bg-slate-50/50 border-b border-slate-100">
                            <th class="px-6 py-4 text-xs font-bold uppercase tracking-wider text-slate-400">Product Item</th>
                            <th class="px-6 py-4 text-xs font-bold uppercase tracking-wider text-slate-400">SKU</th>
                            <th class="px-6 py-4 text-xs font-bold uppercase tracking-wider text-slate-400 text-center">Qty</th>
                            <th class="px-6 py-4 text-xs font-bold uppercase tracking-wider text-slate-400 text-right">Price</th>
                            <th class="px-6 py-4 text-xs font-bold uppercase tracking-wider text-slate-400 text-right">Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-sm">
                        @foreach($order->orderItems as $item)
                            <tr class="hover:bg-slate-50/50 transition">
                                <td class="px-6 py-4 font-semibold text-slate-800">
                                    {{ $item->product_name_snapshot }}
                                </td>
                                <td class="px-6 py-4 text-slate-500 font-mono font-medium">
                                    {{ $item->sku_snapshot }}
                                </td>
                                <td class="px-6 py-4 text-slate-600 font-semibold text-center">
                                    {{ $item->quantity }}
                                </td>
                                <td class="px-6 py-4 text-slate-600 font-medium text-right">
                                    <x-money :amount="$item->final_price" />
                                </td>
                                <td class="px-6 py-4 font-extrabold text-slate-800 text-right">
                                    <x-money :amount="$item->final_price * $item->quantity" />
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </x-admin.data-table>

                <!-- Order Totals summary block -->
                <div class="mt-6 border-t border-slate-100 pt-6 flex flex-col items-end gap-2.5 text-sm font-medium text-slate-500">
                    <div class="flex justify-between w-64">
                        <span>Subtotal</span>
                        <span class="text-slate-700 font-semibold"><x-money :amount="$order->subtotal" /></span>
                    </div>
                    @if($order->discount_total > 0)
                        <div class="flex justify-between w-64 text-emerald-600 font-semibold">
                            <span>Discount Total</span>
                            <span>-<x-money :amount="$order->discount_total" /></span>
                        </div>
                    @endif
                    <div class="flex justify-between w-64 text-base font-extrabold text-slate-800 border-t border-slate-100 pt-3 mt-1.5">
                        <span>Grand Total</span>
                        <span class="text-slate-900"><x-money :amount="$order->total" /></span>
                    </div>
                </div>
            </x-admin.card>

            <!-- Order Notes Card -->
            @if($order->notes)
                <x-admin.card title="Customer Notes">
                    <div class="p-4 bg-slate-50 border border-slate-100 text-slate-600 rounded-xl text-sm font-medium leading-relaxed">
                        {{ $order->notes }}
                    </div>
                </x-admin.card>
            @endif

        </div>

        <!-- Right: Status Update, Customer Info, Payment Details -->
        <div class="space-y-8" x-data="orderManager({
    orderId: {{ $order->id }},
    status: '{{ $order->status }}',
    hasProof: {{ $order->latestPayment && $order->latestPayment->proof_path ? 'true' : 'false' }},
    paymentStatus: '{{ $order->latestPayment->status ?? 'none' }}'
})">

    <!-- WarningBox Component (Alpine) -->
    <template x-teleport="body">
        <div x-show="warning.show" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 backdrop-blur-sm" style="display: none;" x-transition>
            <div class="bg-white rounded-2xl shadow-xl max-w-sm w-full mx-4 p-6" @click.away="warning.show = false">
                <h3 class="text-lg font-bold text-slate-900 mb-2" x-text="warning.title"></h3>
                <p class="text-slate-600 mb-6 text-sm leading-relaxed" x-text="warning.message"></p>
                <div class="flex justify-end gap-3">
                    <button @click="warning.show = false" class="px-4 py-2 rounded-xl text-sm font-medium text-slate-600 hover:bg-slate-50 transition">Cancel</button>
                    <button @click="confirmAction()" x-show="warning.actionUrl" class="px-4 py-2 rounded-xl text-sm font-bold text-white bg-slate-900 hover:bg-slate-800 transition shadow-sm" x-text="warning.confirmText"></button>
                    <button @click="warning.show = false" x-show="!warning.actionUrl" class="px-4 py-2 rounded-xl text-sm font-bold text-white bg-slate-900 hover:bg-slate-800 transition shadow-sm" x-text="warning.confirmText"></button>
                </div>
            </div>
        </div>
    </template>
            
            <!-- Customer Information Card -->
            <x-admin.card title="Customer Information">
                <div class="space-y-4">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-3 text-sm">
                        <span class="text-slate-400 font-semibold">Name</span>
                        <div class="flex items-center gap-3">
                            <span class="text-slate-800 font-bold">{{ $order->user->name ?? 'Guest' }}</span>
                            @if($order->user_id)
                                <button type="button" @click="window.location.href = `/admin/users/{{ $order->user_id }}`" 
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold text-sky-600 bg-sky-50 hover:bg-sky-100 transition border border-sky-100">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                    View Profile
                                </button>
                            @endif
                        </div>
                    </div>
                    <div class="flex items-center justify-between border-b border-slate-100 pb-3 text-sm">
                        <span class="text-slate-400 font-semibold">Email</span>
                        <span class="text-slate-800 font-bold truncate max-w-[160px]" title="{{ $order->user->email ?? 'N/A' }}">{{ $order->user->email ?? 'N/A' }}</span>
                    </div>
                    @if($order->user && $order->user->phone_num)
                        <div class="flex items-center justify-between border-b border-slate-100 pb-3 text-sm">
                            <span class="text-slate-400 font-semibold">Phone</span>
                            <span class="text-slate-800 font-bold">{{ $order->user->phone_num }}</span>
                        </div>
                    @endif
                    @if($order->user && $order->user->address)
                        <div class="pt-1">
                            <span class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Shipping Address</span>
                            <div class="text-xs text-slate-600 font-medium bg-slate-50 border border-slate-100 p-3.5 rounded-xl leading-relaxed">
                                {{ $order->user->address }}
                            </div>
                        </div>
                    @endif
                </div>
            </x-admin.card>

            <!-- Order Action Sequence -->
            <x-admin.card title="Order Action">
                <div x-show="nextActionLabel" class="space-y-4" style="display: none;">
                    <p class="text-sm text-slate-500">Next logical step for this order:</p>
                    <button @click="promptAction()" :disabled="loading" class="w-full inline-flex justify-center items-center py-2.5 px-4 border border-transparent shadow-sm text-xs font-bold uppercase tracking-wider rounded-xl text-white bg-slate-800 hover:bg-slate-900 focus:outline-none transition disabled:opacity-50">
                        <span x-text="loading ? 'Processing...' : nextActionLabel"></span>
                    </button>
                </div>
                <div x-show="!nextActionLabel" class="text-sm font-medium text-slate-400 italic py-2" style="display: none;">
                    No further actions available for this order status.
                </div>
            </x-admin.card>

            <!-- Payment Information & Management -->
            <x-admin.card title="Payment Details">
                <div class="space-y-4">
                    <template x-if="status === 'pending_payment'">
                        <div class="mb-2">
                            <span class="px-2.5 py-0.5 inline-flex text-xxs font-bold rounded-full bg-amber-50 text-amber-700 border border-amber-200/50 uppercase tracking-wider">Awaiting Payment</span>
                        </div>
                    </template>

                    @if($order->latestPayment)
                        <div class="space-y-3.5 text-sm">
                            <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                                <span class="text-slate-400 font-semibold">Payment Method</span>
                                <span class="text-slate-800 font-bold">{{ ucfirst($order->latestPayment->payment_method ?? $order->latestPayment->provider ?? 'Unknown') }}</span>
                            </div>
                            @if($order->latestPayment->transaction_ref)
                                <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                                    <span class="text-slate-400 font-semibold">Transaction Ref</span>
                                    <span class="text-slate-800 font-mono font-bold">{{ $order->latestPayment->transaction_ref }}</span>
                                </div>
                            @endif
                            <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                                <span class="text-slate-400 font-semibold">Verification Status</span>
                                <span class="font-extrabold uppercase text-xs tracking-wider"
                                      :class="{
                                        'text-emerald-500': paymentStatus === 'verified',
                                        'text-amber-500': paymentStatus === 'pending',
                                        'text-rose-500': paymentStatus === 'failed' || paymentStatus === 'rejected'
                                      }" x-text="paymentStatus">
                                </span>
                            </div>
                        </div>

                        <!-- Payment Proof Upload Area -->
                        @if($order->latestPayment->proof_path)
                            <div class="mt-5 border-t border-slate-100 pt-4" x-data="{ zoomOpen: false }">
                                <span class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2.5">Payment Receipt</span>
                                <span class="hidden">{{ $order->latestPayment->proof_path }}</span>
                                
                                <div class="flex justify-center">
                                    <!-- Receipt Image Preview -->
                                    <img src="{{ route('admin.payments.proof', $order->latestPayment) }}" 
                                         alt="Payment Receipt" 
                                         class="max-w-xs w-full object-contain rounded-xl shadow-sm border border-slate-100 cursor-pointer hover:opacity-90 hover:shadow-md transition duration-200" 
                                         @click="zoomOpen = true">
                                    
                                    <!-- Zoom Modal Overlay (Alpine.js) -->
                                    <div x-show="zoomOpen" 
                                         x-transition:enter="transition ease-out duration-200"
                                         x-transition:enter-start="opacity-0"
                                         x-transition:enter-end="opacity-100"
                                         x-transition:leave="transition ease-in duration-150"
                                         x-transition:leave-start="opacity-100"
                                         x-transition:leave-end="opacity-0"
                                         style="display: none;" 
                                         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm" 
                                         @click.stop="zoomOpen = false">
                                        <img src="{{ route('admin.payments.proof', $order->latestPayment) }}" 
                                             alt="Payment Receipt Zoomed" 
                                             class="max-h-[85vh] max-w-[85vw] object-contain rounded-xl shadow-2xl" 
                                             @click.stop>
                                        <button @click="zoomOpen = false" 
                                                class="absolute top-6 right-6 text-white/70 hover:text-white transition-colors bg-white/10 hover:bg-white/20 rounded-full p-2.5 focus:outline-none">
                                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>

                                <!-- Approval forms removed, handled by main Order Action button -->
                            </div>
                        @endif
                    @elseif($order->status !== 'pending_payment')
                        <div class="text-xs font-medium text-slate-400 italic text-center py-4 bg-slate-50/50 border border-dashed border-slate-100 rounded-xl">
                            No payment records found yet.
                        </div>
                    @endif

                    @if($order->payment_proof)
                        <div class="mt-4">
                            <img src="{{ asset($order->payment_proof) }}" class="rounded-xl shadow border border-slate-100" />
                        </div>
                    @endif
                </div>
            </x-admin.card>

        </div>

    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('orderManager', (initialState) => ({
        ...initialState,
        loading: false,
        warning: {
            show: false,
            title: '',
            message: '',
            confirmText: '',
            actionUrl: '',
            actionMethod: '',
            actionData: null
        },
        
        get nextActionLabel() {
            if (this.status === 'pending_payment') return this.hasProof ? 'Verify Proof' : 'Mark as Paid';
            if (this.status === 'paid') return 'Ship Order';
            if (this.status === 'shipped') return 'Deliver Order';
            return null;
        },
        
        promptAction() {
            if (this.status === 'pending_payment') {
                this.showWarning(
                    this.hasProof ? 'Verify Payment Proof' : 'Mark Order as Paid',
                    this.hasProof ? 'Are you sure you want to approve this payment proof?' : 'Are you sure you want to mark this order as paid without proof?',
                    this.hasProof ? 'Verify' : 'Mark Paid',
                    `/admin/orders/${this.orderId}/process-payment`,
                    'POST'
                );
            } else if (this.status === 'paid') {
                this.showWarning(
                    'Ship Order',
                    'Are you sure you want to mark this order as shipped?',
                    'Ship',
                    `/admin/orders/${this.orderId}/status`,
                    'PATCH',
                    { status: 'shipped' }
                );
            } else if (this.status === 'shipped') {
                this.showWarning(
                    'Deliver Order',
                    'Are you sure you want to mark this order as completed (delivered)?',
                    'Deliver',
                    `/admin/orders/${this.orderId}/status`,
                    'PATCH',
                    { status: 'completed' }
                );
            }
        },

        showWarning(title, message, confirmText, url, method, data = null) {
            this.warning = {
                show: true,
                title,
                message,
                confirmText,
                actionUrl: url,
                actionMethod: method,
                actionData: data
            };
        },

        async confirmAction() {
            if (!this.warning.actionUrl) {
                this.warning.show = false;
                return;
            }
            this.loading = true;
            this.warning.show = false;
            try {
                const response = await fetch(this.warning.actionUrl, {
                    method: this.warning.actionMethod,
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    },
                    body: this.warning.actionData ? JSON.stringify(this.warning.actionData) : null
                });
                
                const result = await response.json();
                
                if (response.ok) {
                    this.status = result.status || (this.warning.actionData ? this.warning.actionData.status : this.status);
                    if (this.warning.actionUrl.includes('process-payment')) {
                        this.status = 'paid';
                        this.paymentStatus = 'verified';
                    }
                } else {
                    this.showWarning('Error', result.error || 'An unexpected error occurred.', 'OK', null, null);
                    this.warning.show = true;
                }
            } catch (error) {
                console.error(error);
                this.showWarning('Error', 'Network error occurred.', 'OK', null, null);
                this.warning.show = true;
            } finally {
                this.loading = false;
            }
        }
    }));
});
</script>
@endpush

