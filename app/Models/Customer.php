<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'customer_code',
        'full_name',
        'contact_email',
        'phone',
        'address',
        'notes',
        'email_marketing_consent',
        'consent_updated_at',
    ];

    protected function casts(): array
    {
        return [
            'email_marketing_consent' => 'boolean',
            'consent_updated_at' => 'datetime',
        ];
    }

    /**
     * Akun login milik pelanggan, jika tersedia.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Semua pesanan milik pelanggan.
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Semua feedback yang dikirim pelanggan.
     */
    public function feedback(): HasMany
    {
        return $this->hasMany(Feedback::class);
    }
}