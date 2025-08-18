<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Email extends Model
{
    use HasFactory;

    protected $fillable = [
        'subject',
        'body',
        'sender_id',
        'status',
        'sent_at'
    ];

    protected $casts = [
        'sent_at' => 'datetime',
    ];

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function recipients()
    {
        return $this->hasMany(EmailRecipient::class);
    }

    public function attachments()
    {
        return $this->hasMany(EmailAttachment::class);
    }

    public function getToRecipients()
    {
        return $this->recipients()->where('type', 'to')->get();
    }

    public function getCcRecipients()
    {
        return $this->recipients()->where('type', 'cc')->get();
    }

    public function getBccRecipients()
    {
        return $this->recipients()->where('type', 'bcc')->get();
    }
}
