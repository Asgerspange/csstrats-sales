<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmailRecipient extends Model
{
    use HasFactory;

    protected $fillable = [
        'email_id',
        'recipient_email',
        'recipient_name',
        'type',
        'delivered_at',
        'opened_at'
    ];

    protected $casts = [
        'delivered_at' => 'datetime',
        'opened_at' => 'datetime',
    ];

    public function email()
    {
        return $this->belongsTo(Email::class);
    }
}
