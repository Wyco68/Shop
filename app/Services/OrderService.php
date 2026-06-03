<?php

namespace App\Services;

use App\Events\OrderPlaced;
use App\Events\OrderStatusUpdated;
use App\Exceptions\InvalidOrderTransitionException;
use App\Models\Cart;
use App\Models\Discount;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class OrderService
{
    /**
     * Strict state machine transition map.
     * Key = current status, Value = allowed next statuses.
     */
    private const TRANSITIONS = [
        Order::STATUS_PENDING_PAYMENT => [
            Order::STATUS_PENDING,
            Order::STATUS_CANCELLED,
        ],
        Order::STATUS_PENDING => [
            Order::STATUS_PAID,        // paid ≈ confirmed
            Order::STATUS_CANCELLED,
        ],
        Order::STATUS_PAID => [         // paid ≈ confirmed
            Order::STATUS_PROCESSING,
            Order::STATUS_SHIPPED,
            Order::STATUS_CANCELLED,
        ],
        Order::STATUS_PROCESSING => [
            Order::STATUS_SHIPPED,
            Order::STATUS_CANCELLED,
        ],
        Order::STATUS_SHIPPED => [
            Order::STATUS_COMPLETED,           // delivered
            'return_requested',
        ],
        'return_requested' => [
            'returned',
        ],
        // Terminal states — no further transitions allowed
        Order::STATUS_COMPLETED  => [],
        Order::STATUS_CANCELLED  => [],
        Order::STATUS_REFUNDED   => [],
        'returned'               => [],
    ];

    public function __construct(
        private readonly InventoryService $inventoryService,
        private readonly CartService $cartService,
    ) {}

    // ----------------------------------------------------------------
    // Order Creation
    // ----------------------------------------------------------------

    public function createFromCart(
        User $user,
        Cart $cart,
        int $paymentMethodId,
        ?string $notes = null,
    ): Order {
        $order = DB::transaction(function () use ($user, $cart, $paymentMethodId, $notes) {
            $summary = $this->cartService->getCartSummary($cart);

            if (empty($summary['items'])) {
                throw new \RuntimeException('Cart is empty.');
            }

            // Validate stock availability for all items (pre-check, no lock)
            foreach ($summary['items'] as $item) {
                if (!$this->inventoryService->checkAvailability($item['variant']->id, $item['quantity'])) {
                    throw new \RuntimeException("Insufficient stock for {$item['product']->name} ({$item['variant']->sku})");
                }
            }

            $order = Order::create([
                'user_id'           => $user->id,
                'payment_method_id' => $paymentMethodId,
                'status'            => Order::STATUS_PENDING_PAYMENT,
                'subtotal'          => $summary['subtotal'],
                'discount_total'    => $summary['discount_total'],
                'total'             => $summary['total'],
                'currency'          => config('shop.currency', 'USD'),
                'notes'             => $notes,
            ]);

            // Create initial status history record
            OrderStatusHistory::create([
                'order_id'    => $order->id,
                'from_status' => null,
                'to_status'   => $order->status,
                'note'        => 'Order created.',
            ]);

            foreach ($summary['items'] as $item) {
                $order->orderItems()->create([
                    'product_id'             => $item['product']->id,
                    'variant_id'             => $item['variant']->id,
                    'product_name_snapshot'  => $item['product']->name,
                    'sku_snapshot'           => $item['variant']->sku,
                    'unit_price'             => $item['unit_price'],
                    'discount_amount'        => $item['discount_amount'] / max($item['quantity'], 1),
                    'final_price'            => ($item['final_line_total']) / max($item['quantity'], 1),
                    'quantity'               => $item['quantity'],
                ]);

                // Reserve stock (uses lockForUpdate internally)
                $this->inventoryService->reserveStock(
                    $item['variant']->id,
                    $item['quantity'],
                    $order->id
                );
            }

            $this->cartService->clearCart($cart);

            return $order->load('orderItems');
        });

        // Dispatch event AFTER transaction commits (afterCommit listener)
        event(new OrderPlaced($order));

        return $order;
    }

    // ----------------------------------------------------------------
    // Order Cancellation
    // ----------------------------------------------------------------

    public function cancelOrder(Order $order): void
    {
        if (!$order->isCancellable()) {
            throw new \RuntimeException('Order cannot be cancelled in its current state.');
        }

        // Capture before transaction so event dispatch has correct original status
        $fromStatus = $order->status;

        DB::transaction(function () use ($order, $fromStatus) {
            foreach ($order->orderItems as $item) {
                if ($item->variant_id) {
                    $this->inventoryService->releaseStock(
                        $item->variant_id,
                        $item->quantity,
                        $order->id
                    );
                }
            }

            $order->update(['status' => Order::STATUS_CANCELLED]);

            OrderStatusHistory::create([
                'order_id'    => $order->id,
                'from_status' => $fromStatus,
                'to_status'   => Order::STATUS_CANCELLED,
                'note'        => 'Order cancelled.',
            ]);
        });

        event(new OrderStatusUpdated($order->fresh(), $fromStatus, Order::STATUS_CANCELLED));
    }

    // ----------------------------------------------------------------
    // Process Payment (Single Action logic)
    // ----------------------------------------------------------------

    public function processPayment(Order $order, \App\Services\PaymentService $paymentService): void
    {
        $payment = $order->latestPayment;
        $hasProof = $payment && $payment->proof_path;

        if ($hasProof) {
            $paymentService->verifyPayment($payment);
        } else {
            $payment = $payment ?? $paymentService->initiatePayment($order);
            $paymentService->verifyPayment($payment);
        }
    }

    // ----------------------------------------------------------------
    // Mark as Paid (called by PaymentService after verification)
    // ----------------------------------------------------------------

    public function markAsPaid(Order $order, Payment $payment): void
    {
        DB::transaction(function () use ($order) {
            // Idempotency guard — prevent double-confirmation
            $fresh = Order::lockForUpdate()->findOrFail($order->id);

            if ($fresh->status !== Order::STATUS_PENDING_PAYMENT) {
                return; // Already processed
            }

            foreach ($fresh->orderItems as $item) {
                if ($item->variant_id) {
                    $this->inventoryService->deductStock(
                        $item->variant_id,
                        $item->quantity,
                        $fresh->id
                    );
                }
            }

            $fromStatus = $fresh->status;
            $fresh->update(['status' => Order::STATUS_PAID]);

            OrderStatusHistory::create([
                'order_id'    => $fresh->id,
                'from_status' => $fromStatus,
                'to_status'   => Order::STATUS_PAID,
                'note'        => 'Payment verified.',
            ]);

            // Sync $order reference for event dispatch
            $order->status = $fresh->status;
        });

        event(new OrderStatusUpdated($order->fresh(), Order::STATUS_PENDING_PAYMENT, Order::STATUS_PAID));
    }

    // ----------------------------------------------------------------
    // State Machine Status Update
    // ----------------------------------------------------------------

    /**
     * Strictly validated status transition. Enforces the transition map.
     * Wraps everything in a transaction and records history.
     *
     * @throws InvalidOrderTransitionException
     */
    public function updateStatus(Order $order, string $newStatus, ?string $note = null): void
    {
        // Capture before transaction for event dispatch
        $fromStatus = $order->status;

        DB::transaction(function () use ($order, $newStatus, $note, &$fromStatus) {
            // Re-fetch with lock to prevent concurrent mutations
            $fresh = Order::lockForUpdate()->findOrFail($order->id);

            $this->validateTransition($fresh, $newStatus);

            // Use the locked fresh status as the authoritative fromStatus
            $fromStatus = $fresh->status;
            $fresh->update(['status' => $newStatus]);

            OrderStatusHistory::create([
                'order_id'    => $fresh->id,
                'from_status' => $fromStatus,
                'to_status'   => $newStatus,
                'note'        => $note,
            ]);

            // Update the passed-in reference so callers see new status
            $order->status = $newStatus;
        });

        event(new OrderStatusUpdated($order->fresh(), $fromStatus, $newStatus));
    }

    // ----------------------------------------------------------------
    // Transition Validation (private)
    // ----------------------------------------------------------------

    /**
     * @throws InvalidOrderTransitionException
     */
    private function validateTransition(Order $order, string $newStatus): void
    {
        $allowed = self::TRANSITIONS[$order->status] ?? null;

        if ($allowed === null) {
            throw new InvalidOrderTransitionException($order->status, $newStatus);
        }

        if (!in_array($newStatus, $allowed, true)) {
            throw new InvalidOrderTransitionException($order->status, $newStatus);
        }
    }
}
