<?php

namespace App\Models\CsStrats;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Stratbook extends Model
{
    use SoftDeletes;
    protected $connection = 'mysql';

    protected $table = 'stratbooks';

    protected $fillable = [
        'id',
        'uuid',
        'stratbook_name',
        'team_id',
        'created_by'
    ];

    protected $hidden = [
        'deleted_at'
    ];

    public function tactics()
    {
        return $this->hasMany(StratbookTactic::class, 'stratbook_id');
    }

    public function team()
    {
        return $this->belongsTo(Team::class, 'team_id');
    }
}
