<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderStatusUpdatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly Order $order,
        private readonly string $fromStatus,
        private readonly string $toStatus,
    ) {}

    public function via(object $notifiable): array
    {
        return $notifiable->notify_order_status_email ? ['mail'] : [];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Order #{$this->order->id} Status Updated")
            ->markdown('mail.orders.status-updated', [
                'user' => $notifiable,
                'order' => $this->order,
                'fromLabel' => ucfirst(str_replace('_', ' ', $this->fromStatus)),
                'toLabel' => ucfirst(str_replace('_', ' ', $this->toStatus)),
            ]);
    }
}
