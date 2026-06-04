<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    // Core statuses (existing — do NOT remove)
    const STATUS_PENDING         = 'pending';
    const STATUS_PENDING_PAYMENT = 'pending_payment';
    const STATUS_PAID            = 'paid';
    const STATUS_PROCESSING      = 'processing';
    const STATUS_SHIPPED         = 'shipped';
    const STATUS_COMPLETED       = 'completed';
    const STATUS_CANCELLED       = 'cancelled';
    const STATUS_REFUNDED        = 'refunded';

    // Extended state machine statuses
    // confirmed ≈ paid (order confirmed after payment verified)
    const STATUS_CONFIRMED        = 'paid';
    // delivered ≈ completed
    const STATUS_DELIVERED        = 'completed';
    const STATUS_RETURN_REQUESTED = 'return_requested';
    const STATUS_RETURNED         = 'returned';

    protected $fillable = [
        'user_id', 'payment_method_id', 'status', 'subtotal', 'discount_total', 'total', 'currency', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'discount_total' => 'decimal:2',
            'total' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Order $order): void {
            if (empty($order->currency)) {
                $order->currency = \App\Support\Money::currency();
            }
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function paymentMethod()
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function latestPayment()
    {
        return $this->hasOne(Payment::class)->latestOfMany();
    }

    public function statusHistories()
    {
        return $this->hasMany(OrderStatusHistory::class)->orderBy('id');
    }

    public function refundRequest()
    {
        return $this->hasOne(RefundRequest::class);
    }

    public function isPaid(): bool
    {
        return $this->status === self::STATUS_PAID;
    }

    public function isCancellable(): bool
    {
        return in_array($this->status, [
            self::STATUS_PENDING,
            self::STATUS_PENDING_PAYMENT,
            self::STATUS_PROCESSING,
        ]);
    }

    public function isRefundable(): bool
    {
        return in_array($this->status, [
            self::STATUS_COMPLETED,
            self::STATUS_DELIVERED,
        ]);
    }
}