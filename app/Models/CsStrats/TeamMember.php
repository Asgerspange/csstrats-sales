<?php

namespace App\Models\CsStrats;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class TeamMember extends Model
{
    protected $table = 'team_members';

    protected $fillable = [
        'user_id',
        'team_id',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'team_id' => 'integer',
    ];

    public $timestamps = false;
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function team()
    {
        return $this->belongsTo(Team::class);
    }
}
