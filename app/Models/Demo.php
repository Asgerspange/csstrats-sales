<?php

namespace App\Models;

use App\Events;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\{
    Model,
    SoftDeletes,
};

class Demo extends Model
{
    use HasFactory, SoftDeletes;
 /**
     * The connection name for the model.
     *
     * @var string
     */
    protected $connection = 'mysql';
    protected $table = 'demos';

    protected $fillable = [
        'upload_id',
        'name',
        'chunk_index',
        'owner',
        'map',
        'meta',
        'visible_to',
        'created_at',
        'version',
    ];

    protected $casts = [
        'visible_to' => 'array',
        'meta' => 'array',
    ];

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner');
    }

    public function BunnyUpload()
    {
        return $this->hasOne(BunnyUpload::class, 'id', 'upload_id');
    }
}
