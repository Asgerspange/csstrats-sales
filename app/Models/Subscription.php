<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    public $timestamps = false;
    protected $fillable = [
        'sub_id',
        'currency',
        'customer',
        'latest_invoice',
        'plan',
        'status',
        'current_period_start',
        'current_period_end',
        'coupon',
        'created'
    ];

    protected $table = 'subscriptions';

    protected $casts = [
        'plan' => 'array',
        'coupon' => 'array'
    ];
}
