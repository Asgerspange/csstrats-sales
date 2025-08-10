<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    public $timestamps = false;
    protected $fillable = [
        'id',
        'cus_id',
        'object',
        'address',
        'balance',
        'created',
        'currency',
        'description',
        'email',
        'invoice_prefix',
        'invoice_settings',
        'metadata',
        'name',
        'phone',
        'subscribed'
    ];

    protected $casts = [
        'address' => 'array',
        'invoice_settings' => 'array',
        'metadata' => 'array',
    ];

    protected $appends = ['subscribed']; 

    public function user()
    {
        return $this->belongsTo(User::class, 'cus_id', 'stripe_id');
    }

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class, 'customer', 'cus_id');
    }

    public function getSubscribedAttribute()
    {
        return $this->subscriptions()->where('status', 'active')->exists();
    }
}
