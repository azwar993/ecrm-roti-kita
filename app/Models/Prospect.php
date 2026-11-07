<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Prospect extends Model
{
    use HasFactory;

    public const STATUS_NEW = 'new';
    public const STATUS_CONTACTED = 'contacted';
    public const STATUS_CONVERTED = 'converted';
    public const STATUS_LOST = 'lost';

    protected $fillable = [
        'name', 'email', 'phone', 'source', 'status', 'notes', 'follow_up_at',
        'assigned_to', 'converted_customer_id',
    ];

    protected function casts(): array
    {
        return ['follow_up_at' => 'datetime'];
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function convertedCustomer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'converted_customer_id');
    }
}
