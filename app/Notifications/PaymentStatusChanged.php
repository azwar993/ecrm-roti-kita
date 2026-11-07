<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class PaymentStatusChanged extends Notification
{
    use Queueable;

    public function __construct(public Order $order, public string $status) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $this->order->loadMissing('payment', 'customer');
        $messages = [
            'paid' => 'Pembayaran pesanan telah dikonfirmasi.',
            'rejected' => 'Bukti pembayaran ditolak. Silakan periksa alasan dan unggah ulang.',
            'refunded' => 'Pembayaran pesanan telah dicatat sebagai refund.',
            'failed' => 'Pembayaran belum berhasil diverifikasi.',
        ];

        return [
            'kind' => 'payment_'.$this->status,
            'order_id' => $this->order->id,
            'order_number' => $this->order->order_number,
            'customer_name' => $this->order->customer_name_snapshot ?: $this->order->customer?->full_name,
            'method' => $this->order->payment?->method,
            'amount' => $this->order->payment?->amount,
            'message' => $messages[$this->status] ?? 'Status pembayaran diperbarui.',
        ];
    }
}
