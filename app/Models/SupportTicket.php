<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupportTicket extends Model
{
    use HasFactory;

    public const CATEGORIES = ['order', 'payment', 'product', 'delivery', 'promotion', 'account', 'other'];

    public const STATUSES = ['open', 'in_progress', 'waiting_customer', 'resolved', 'closed'];

    public const PRIORITIES = ['low', 'normal', 'high', 'urgent'];

    protected $fillable = ['ticket_number', 'user_id', 'order_id', 'category', 'subject', 'status', 'priority', 'resolved_at', 'closed_at'];

    protected function casts(): array
    {
        return ['resolved_at' => 'datetime', 'closed_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(TicketMessage::class);
    }
}
