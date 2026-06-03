<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use App\Exceptions\InvalidOrderTransitionException;
use App\Services\OrderService;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OrderController extends Controller
{
    public function __construct(
        private readonly PaymentService $paymentService,
        private readonly OrderService $orderService,
    ) {}

    public function index(Request $request)
    {
        $query = Order::with('user', 'latestPayment', 'paymentMethod')
            ->orderByDesc('created_at');

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $orders = $query->paginate(20)->withQueryString();

        return view('admin.orders.index', compact('orders'));
    }

    public function show(Order $order)
    {
        $order->load('user', 'orderItems', 'payments', 'paymentMethod', 'latestPayment');

        return view('admin.orders.show', compact('order'));
    }

    public function downloadProof(Payment $payment): StreamedResponse
    {
        $privateDisk = config('filesystems.private_disk', 'private');
        if (!$payment->proof_path || !Storage::disk($privateDisk)->exists($payment->proof_path)) {
            abort(404);
        }

        return Storage::disk($privateDisk)->response($payment->proof_path);
    }

    public function processPayment(Order $order)
    {
        if ($order->status !== Order::STATUS_PENDING_PAYMENT) {
            if (request()->expectsJson()) {
                return response()->json(['error' => 'Only orders awaiting payment can be marked as paid.'], 400);
            }
            return redirect()->back()
                ->with('error', 'Only orders awaiting payment can be marked as paid.');
        }

        $this->orderService->processPayment($order, $this->paymentService);

        if (request()->expectsJson()) {
            return response()->json([
                'success' => 'Order marked as paid.',
                'status' => $order->status
            ]);
        }
        return redirect()->back()->with('success', 'Order marked as paid.');
    }

    public function verifyPayment(Payment $payment)
    {
        $this->paymentService->verifyPayment($payment);

        return redirect()->back()->with('success', 'Payment verified. Order marked as paid.');
    }

    public function rejectPayment(Payment $payment)
    {
        $this->paymentService->rejectPayment($payment);

        return redirect()->back()->with('success', 'Payment rejected. Order cancelled.');
    }

    public function updateStatus(Request $request, Order $order)
    {
        $allowedStatuses = [
            Order::STATUS_PENDING,
            Order::STATUS_PENDING_PAYMENT,
            Order::STATUS_PAID,
            Order::STATUS_PROCESSING,
            Order::STATUS_SHIPPED,
            Order::STATUS_COMPLETED,
            Order::STATUS_CANCELLED,
            Order::STATUS_REFUNDED,
            'return_requested',
            'returned',
        ];

        $request->validate([
            'status' => ['required', 'string', 'in:' . implode(',', $allowedStatuses)],
            'note'   => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $this->orderService->updateStatus(
                $order,
                $request->input('status'),
                $request->input('note'),
            );
        } catch (InvalidOrderTransitionException $e) {
            if ($request->expectsJson()) {
                return response()->json(['error' => $e->getMessage()], 400);
            }
            return redirect()->back()->with('error', $e->getMessage());
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => 'Order status updated.',
                'status' => $order->status
            ]);
        }
        return redirect()->back()->with('success', 'Order status updated.');
    }
}
