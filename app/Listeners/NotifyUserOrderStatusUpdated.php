<?php

namespace App\Listeners;

use App\Events\OrderStatusUpdated;
use App\Models\Notification;
use App\Notifications\OrderStatusUpdatedNotification;

class NotifyUserOrderStatusUpdated
{
    public bool $afterCommit = true;

    public function __construct(private \App\Services\NotificationService $notificationService) {}

    public function handle(OrderStatusUpdated $event): void
    {
        $order = $event->order;

        // Load the order owner
        $user = $order->user;
        if (!$user) {
            return;
        }

        $from = ucfirst(str_replace('_', ' ', $event->fromStatus));
        $to   = ucfirst(str_replace('_', ' ', $event->toStatus));

        $this->notificationService->createForUser(
            $user,
            Notification::TYPE_ORDER_STATUS_UPDATED,
            "Order #{$order->id} Status Updated",
            "Your order #{$order->id} status has changed from '{$from}' to '{$to}'.",
            [
                'order_id'    => $order->id,
                'from_status' => $event->fromStatus,
                'to_status'   => $event->toStatus,
            ]
        );

        $user->notify(new OrderStatusUpdatedNotification($order, $event->fromStatus, $event->toStatus));
    }
}
