<?php

namespace App\Models;

use Illuminate\Database\Eloquent\{
    Model,
    SoftDeletes
};

class Affiliate extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'promocode',
        'email',
        'bank_account',
        'iban',
        'commission_rate',
        'balance',
        'min_payout_amount',
        'total_earned',
        'total_paid',
        'status',
        'last_update',
        'access_token',
    ];

    protected $casts = [
        'last_update' => 'date',
    ];

    protected $dates = [
        'last_update',
    ];

    protected $hidden = [
        'access_token',
    ];
}
