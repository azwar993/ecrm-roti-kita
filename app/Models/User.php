<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasOne;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * Only fields that may safely be mass-assigned from ordinary forms.
     * Never add role/is_active here just to make registration easier.
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * Attributes hidden from arrays and JSON responses.
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Attribute casting for the model.
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    /** One account can be linked to at most one CRM customer profile. */
    public function customer(): HasOne
    {
        return $this->hasOne(Customer::class);
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isCustomer(): bool
    {
        return $this->role === 'customer';
    }
}
