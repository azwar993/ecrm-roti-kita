<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentSetting extends Model
{
    protected $fillable = ['qris_image', 'bank_name', 'bank_account_name', 'bank_account_number'];
}
