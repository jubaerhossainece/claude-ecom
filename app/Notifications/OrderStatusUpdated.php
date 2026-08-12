<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Notifications\Notification;

class OrderStatusUpdated extends Notification
{
    public function __construct(public Order $order, public string $status) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toArray($notifiable): array
    {
        $label = Order::STATUSES[$this->status] ?? ucfirst($this->status);

        $messages = [
            'confirmed' => "Your order {$this->order->order_number} has been confirmed.",
            'processing' => "Your order {$this->order->order_number} is being processed.",
            'shipped' => "Your order {$this->order->order_number} has shipped" . ($this->order->courier_name ? " via {$this->order->courier_name}" : '') . '.',
            'delivered' => "Your order {$this->order->order_number} has been delivered. Enjoy!",
            'cancelled' => "Your order {$this->order->order_number} was cancelled.",
            'returned' => "Your order {$this->order->order_number} was marked as returned.",
        ];

        return [
            'title' => "Order {$label}",
            'message' => $messages[$this->status] ?? "Your order {$this->order->order_number} status changed to {$label}.",
            'order_id' => $this->order->id,
            'order_number' => $this->order->order_number,
            'status' => $this->status,
        ];
    }
}
