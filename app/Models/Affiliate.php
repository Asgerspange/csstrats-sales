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
        'coupon',
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

    protected $appends = [
        'coupon_relation'
    ];

    public function getCouponRelationAttribute()
    {
        return $this->belongsTo(Coupon::class, 'coupon', 'code');
    }

    public function invoices()
    {
        return $this->hasMany(Invoices::class, 'coupon', 'coupon');
    }
}
