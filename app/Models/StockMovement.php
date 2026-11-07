<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMovement extends Model
{
    public $timestamps = false;

    public const TYPE_OPENING = 'opening';
    public const TYPE_PURCHASE_IN = 'purchase_in';
    public const TYPE_ADJUSTMENT = 'adjustment';
    public const TYPE_SALE = 'sale';
    public const TYPE_SALE_REVERSAL = 'sale_reversal';

    protected $fillable = [
        'product_id', 'order_id', 'created_by', 'movement_type', 'quantity_change',
        'stock_before', 'stock_after', 'reason', 'created_at',
    ];

    protected function casts(): array
    {
        return [
            'quantity_change' => 'integer',
            'stock_before' => 'integer',
            'stock_after' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
