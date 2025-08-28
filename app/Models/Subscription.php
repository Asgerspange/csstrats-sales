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
        'items',
        'status',
        'product_id',
        'current_period_start',
        'current_period_end',
        'coupon',
        'created'
    ];

    protected $table = 'subscriptions';

    protected $casts = [
        'plan' => 'array',
        'coupon' => 'array',
        'items' => 'array'
    ];

    public function customerRelation()
    {
        return $this->belongsTo(Customer::class, 'customer', 'cus_id');
    }

    public function package()
    {
        return $this->belongsTo(Package::class, 'product_id', 'prod_id');
    }
}
