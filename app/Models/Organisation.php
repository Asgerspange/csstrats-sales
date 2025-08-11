<?php

namespace App\Models;

use Illuminate\Database\Eloquent\{
    Model,
    SoftDeletes
};

class Organisation extends Model
{
    use SoftDeletes;
    protected $fillable = ['name', 'cvr', 'country', 'address', 'type', 'zip'];
}
