<?php

namespace App\Models\CsStrats;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class StratbookTactic extends Model
{
    use SoftDeletes;
    protected $connection = 'mysql';

    protected $table = 'stratbook_tactics';

    protected $fillable = [
        'id',
        'team',
        'name',
        'player_descriptions',
        'player_loadouts',
        'player_timestamps',
        'type',
        'stratbook_id',
        'description',
        'tags',
        'map',
        'side',
        'video',
        'matchdate',
        'matchlink'
    ];

    public $casts = [
        'team' => 'array',
        'player_descriptions' => 'array',
        'player_loadouts' => 'array',
        'player_timestamps' => 'array',
        'tags' => 'array'
    ];

    protected $hidden = [
        'deleted_at'
    ];
}
