<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Notifications\Notification;

class OrderPlaced extends Notification
{
    public function __construct(public Order $order) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toArray($notifiable): array
    {
        return [
            'title' => 'Order placed',
            'message' => "Your order {$this->order->order_number} has been placed successfully.",
            'order_id' => $this->order->id,
            'order_number' => $this->order->order_number,
        ];
    }
}
