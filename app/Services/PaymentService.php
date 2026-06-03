<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class PaymentService
{
    public function __construct(
        private readonly OrderService $orderService,
    ) {}

    public function initiatePayment(Order $order, ?string $provider = null): Payment
    {
        $provider ??= $order->paymentMethod?->code ?? 'transfer';

        return Payment::create([
            'order_id' => $order->id,
            'provider' => $provider,
            'status'   => Payment::STATUS_PENDING,
            'amount'   => $order->total,
            'currency' => $order->currency ?? config('shop.currency', 'USD'),
        ]);
    }

    public function uploadProof(Payment $payment, UploadedFile $file): Payment
    {
        $hash = hash_file('sha256', $file->getRealPath());

        // Check for duplicate proof
        $duplicate = Payment::where('proof_hash', $hash)
            ->where('id', '!=', $payment->id)
            ->exists();

        if ($duplicate) {
            throw new \RuntimeException('This payment proof has already been submitted for another order.');
        }

        $path = $file->store('payment-proofs', config('filesystems.private_disk', 'private'));

        $payment->update([
            'proof_path' => $path,
            'proof_hash' => $hash,
        ]);

        return $payment->fresh();
    }

    /**
     * Verify a payment and confirm the associated order.
     * Uses idempotency guard: only processes if order is still in pending_payment state.
     */
    public function verifyPayment(Payment $payment): void
    {
        // Reload order with lock to check idempotency
        $order = Order::lockForUpdate()->findOrFail($payment->order_id);

        // Idempotency: skip if already processed
        if ($order->status !== Order::STATUS_PENDING_PAYMENT) {
            return;
        }

        DB::transaction(function () use ($payment, $order) {
            $payment->update([
                'status'  => Payment::STATUS_VERIFIED,
                'paid_at' => now(),
            ]);

            $this->orderService->markAsPaid($order, $payment);
        });
    }

    public function rejectPayment(Payment $payment): void
    {
        DB::transaction(function () use ($payment) {
            $payment->update([
                'status' => Payment::STATUS_FAILED,
            ]);

            $this->orderService->cancelOrder($payment->order);
        });
    }
}
