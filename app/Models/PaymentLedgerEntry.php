<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentLedgerEntry extends Model
{
    use HasFactory;

    public const TYPE_PAID = 'paid';

    public const TYPE_REFUND = 'refund';

    protected $fillable = [
        'payment_id', 'recorded_by', 'entry_type', 'amount', 'occurred_at',
        'reference', 'idempotency_key', 'description',
    ];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'occurred_at' => 'datetime'];
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }
}
