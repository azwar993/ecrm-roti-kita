<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Promotion extends Model
{
    use HasFactory;

    public const TYPE_PERCENTAGE = 'percentage';
    public const TYPE_FIXED = 'fixed';

    protected $fillable = [
        'code', 'name', 'description', 'discount_type', 'discount_value',
        'min_order_amount', 'max_discount_amount', 'starts_at', 'ends_at',
        'usage_limit', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'discount_value' => 'decimal:2',
            'min_order_amount' => 'decimal:2',
            'max_discount_amount' => 'decimal:2',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'usage_limit' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function isCurrentlyValid(): bool
    {
        $now = now();

        return $this->is_active
            && $this->starts_at !== null
            && $this->ends_at !== null
            && $now->greaterThanOrEqualTo($this->starts_at)
            && $now->lessThanOrEqualTo($this->ends_at);
    }
}
