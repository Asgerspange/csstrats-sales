<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Invoices extends Model
{
    public $timestamps = false;
    protected $fillable = [
        'invoice_id',
        'billing_reason',
        'collection_method',
        'currency',
        'customer',
        'discounts',
        'coupon',
        'invoice_pdf',
        'data',
        'sub_id',
        'subtotal',
        'subtotal_excluding_tax',
        'status_transitions',
        'payment_interval',
        'created'
    ];

    protected $table = 'invoices';
    protected $casts = [
        'data' => 'array',
        'discounts' => 'array',
        'status_transitions' => 'array',
        'created' => 'datetime',
    ];

    public function customerRelation()
    {
        return $this->belongsTo(Customer::class, 'customer', 'cus_id');
    }

    public function subscription()
    {
        return $this->belongsTo(Subscription::class, 'sub_id', 'sub_id');
    }
    
    public function couponRelation()
    {
        return $this->belongsTo(Coupon::class, 'coupon', 'coupon_id');
    }
}
