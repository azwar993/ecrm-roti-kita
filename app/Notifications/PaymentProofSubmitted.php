<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class PaymentProofSubmitted extends Notification
{
    use Queueable;

    public function __construct(public Order $order, public bool $resubmitted = false) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $order = $this->order->loadMissing('payment');
        return [
            'kind' => $this->resubmitted ? 'payment_proof_resubmitted' : 'payment_proof_submitted',
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'customer_name' => $order->customer_name_snapshot,
            'method' => $order->payment?->method,
            'amount' => $order->payment?->amount,
            'message' => $this->resubmitted ? 'Customer mengirim ulang bukti pembayaran.' : 'Customer mengirim bukti pembayaran.',
        ];
    }
}
