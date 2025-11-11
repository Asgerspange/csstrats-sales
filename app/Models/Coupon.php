<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    protected $fillable = [
        'coupon_id',
        'name',
        'amount_off',
        'percent_off',
        'currency',
        'duration',
        'duration_in_months',
        'valid',
        'redeem_by',
        'max_redemptions',
        'times_redeemed',
        'promotion_codes',
    ];

    protected $casts = [
        'promotion_codes' => 'array',
        'redeem_by' => 'datetime',
        'valid' => 'boolean',
    ];
}
