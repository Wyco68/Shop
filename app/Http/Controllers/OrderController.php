<?php

namespace App\Http\Controllers;

use App\Http\Requests\SubmitOrderPaymentRequest;
use App\Models\Order;
use App\Models\PaymentMethod;
use App\Services\CartService;
use App\Services\OrderService;
use App\Services\PaymentService;
use App\Support\StoreCache;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class OrderController extends Controller
{
    public function __construct(
        private readonly OrderService $orderService,
        private readonly CartService $cartService,
        private readonly PaymentService $paymentService,
    ) {}

    public function index(Request $request)
    {
        $orders = Order::where('user_id', $request->user()->id)
            ->with('orderItems', 'latestPayment', 'paymentMethod', 'user')
            ->orderByDesc('created_at')
            ->paginate(15);

        return view('orders', compact('orders'));
    }

    /** Step 1: select payment method */
    public function create(Request $request)
    {
        $cart = $this->cartService->getOrCreateCart($request->user());
        $summary = $this->cartService->getCartSummary($cart);
        $paymentMethods = Cache::remember(StoreCache::ACTIVE_PAYMENT_METHODS, 3600, function () {
            return PaymentMethod::active()->get();
        });

        return view('orders.create', compact('summary', 'paymentMethods'));
    }

    /** Step 2: show QR / payment instructions */
    public function showInstructions(Request $request)
    {
        $paymentMethodId = $request->session()->get('checkout.payment_method_id');

        if (!$paymentMethodId) {
            return redirect()->route('orders.create')
                ->with('error', 'Please select a payment method first.');
        }

        $paymentMethod = PaymentMethod::active()->findOrFail($paymentMethodId);
        $cart = $this->cartService->getOrCreateCart($request->user());
        $summary = $this->cartService->getCartSummary($cart);

        if (empty($summary['items'])) {
            return redirect()->route('cart.index')
                ->with('error', 'Your cart is empty.');
        }

        return view('orders.checkout-instructions', compact('summary', 'paymentMethod'));
    }

    public function selectPaymentMethod(Request $request)
    {
        $request->validate([
            'payment_method_id' => ['required', 'integer', 'exists:payment_methods,id'],
        ]);

        $paymentMethod = PaymentMethod::active()->findOrFail($request->integer('payment_method_id'));

        $cart = $this->cartService->getOrCreateCart($request->user());
        $summary = $this->cartService->getCartSummary($cart);

        if (empty($summary['items'])) {
            return redirect()->route('cart.index')
                ->with('error', 'Your cart is empty.');
        }

        $request->session()->put('checkout.payment_method_id', $paymentMethod->id);

        return redirect()->route('orders.checkout.instructions');
    }

    /** Step 3: create order (no proof upload) */
    public function store(Request $request)
    {
        $request->validate([
            'payment_method_id' => ['required', 'integer', 'exists:payment_methods,id'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $paymentMethod = PaymentMethod::active()->findOrFail($request->integer('payment_method_id'));

        if ($request->session()->get('checkout.payment_method_id') !== $paymentMethod->id) {
            return redirect()->route('orders.create')
                ->with('error', 'Payment method mismatch. Please start checkout again.');
        }

        $cart = $this->cartService->getOrCreateCart($request->user());

        try {
            $order = $this->orderService->createFromCart(
                $request->user(),
                $cart,
                $paymentMethod->id,
                $request->input('notes'),
            );

            $this->paymentService->initiatePayment($order);

            $request->session()->forget('checkout.payment_method_id');

            return redirect()->route('orders.payment', $order)
                ->with('success', 'Order placed! Confirm your contact details and upload payment proof.');

        } catch (\RuntimeException $e) {
            return redirect()->route('cart.index')
                ->with('error', $e->getMessage());
        }
    }

    /** Confirm contact details and upload payment proof */
    public function payment(Request $request, Order $order)
    {
        $this->authorize('view', $order);

        if ($order->status !== Order::STATUS_PENDING_PAYMENT) {
            return redirect()->route('orders.show', $order);
        }

        $order->load('orderItems', 'latestPayment', 'paymentMethod');
        $user = $request->user();

        return view('orders.payment', compact('order', 'user'));
    }

    public function submitPayment(SubmitOrderPaymentRequest $request, Order $order)
    {
        $this->authorize('view', $order);

        if ($order->status !== Order::STATUS_PENDING_PAYMENT) {
            return redirect()->route('orders.show', $order)
                ->with('error', 'This order is no longer awaiting payment.');
        }

        $payment = $order->latestPayment;

        if (!$payment) {
            $payment = $this->paymentService->initiatePayment($order);
        }

        if ($payment->proof_path) {
            return redirect()->route('orders.show', $order)
                ->with('error', 'Payment proof has already been submitted for this order.');
        }

        $user = $request->user();
        $user->update([
            'phone_num' => $request->input('phone_num'),
            'address' => $request->input('address'),
        ]);

        try {
            $this->paymentService->uploadProof($payment, $request->file('payment_proof'));
        } catch (\RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('orders.show', $order)
            ->with('success', 'Payment proof submitted. We will verify your payment shortly.');
    }

    /** Order tracking */
    public function show(Request $request, Order $order)
    {
        $this->authorize('view', $order);

        $order->load('orderItems', 'latestPayment', 'paymentMethod');
        $user = $request->user();

        return view('orders.show', compact('order', 'user'));
    }
}
