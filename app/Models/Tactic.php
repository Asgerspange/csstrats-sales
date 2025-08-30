<?php

namespace App\Models;

use App\Traits\ReleasableTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tactic extends Model
{
    use SoftDeletes, ReleasableTrait; 
    protected $connection = 'mysql';

    protected $table = 'tactics';

    protected $fillable = [
        'team_name',
        'team',
        'player_descriptions',
        'player_loadouts',
        'player_timestamps',
        'type',
        'description',
        'tags',
        'map',
        'side',
        'video',
        'count',
        'is_released',
        'release_date',
        'matchdate',
        'matchlink',
        'created_by',
        'created_at',
    ];

    public $casts = [
        'team' => 'array',
        'player_descriptions' => 'array',
        'player_loadouts' => 'array',
        'player_timestamps' => 'array',
        'tags' => 'array',
        'is_released' => 'boolean',
        'release_date' => 'datetime',
    ];

    protected $hidden = [
        'updated_at', 'deleted_at'
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // public function demo()
    // {
    //     return $this->hasOne(AdminDemoTactic::class, 'tactic_id', 'id');
    // }
}
