<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class OrderCreated extends Notification
{
    use Queueable;

    public function __construct(public Order $order) {}
    public function via(object $notifiable): array { return ['database']; }
    public function toDatabase(object $notifiable): array
    {
        $order = $this->order->loadMissing('payment');
        return [
            'kind' => 'order_created', 'order_id' => $order->id,
            'order_number' => $order->order_number,
            'customer_name' => $order->customer_name_snapshot,
            'method' => $order->payment?->method, 'amount' => $order->payment?->amount,
            'message' => 'Pesanan baru dibuat.',
        ];
    }
}
