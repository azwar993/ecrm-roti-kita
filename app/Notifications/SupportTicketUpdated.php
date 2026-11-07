<?php

namespace App\Notifications;

use App\Models\SupportTicket;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class SupportTicketUpdated extends Notification
{
    use Queueable;

    public function __construct(public SupportTicket $ticket, public string $event) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'kind' => 'support_ticket_'.$this->event,
            'ticket_id' => $this->ticket->id,
            'ticket_number' => $this->ticket->ticket_number,
            'message' => match ($this->event) {
                'created' => 'Tiket bantuan baru dibuat.',
                'reply' => 'Ada balasan baru pada tiket bantuan.',
                default => 'Status tiket bantuan diperbarui.',
            },
        ];
    }
}
