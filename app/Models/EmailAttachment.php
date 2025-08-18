<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmailAttachment extends Model
{
    use HasFactory;

    protected $fillable = [
        'email_id',
        'file_path',
        'file_name',
        'mime_type',
        'file_size'
    ];

    public function email()
    {
        return $this->belongsTo(Email::class);
    }
}
