<?php

namespace App\Models\CsStrats;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Team extends Model
{
    use SoftDeletes;

    protected $connection = 'mysql';
    protected $table = 'teams';

    protected $fillable = [
        'name',
        'user_ids',
        'owned_by',
        'edit_users',
        'admin_users',
        'settings'
    ];

    protected $casts = [
        'user_ids' => 'array',
        'edit_users' => 'array',
        'admin_users' => 'array',
        'settings' => 'array',
    ];

    public function stratbooks()
    {
        return $this->hasMany(Stratbook::class, 'team_id', 'id');
    }

    public function users()
    {
        return $this->hasManyThrough(
            User::class,
            TeamMember::class,
            'team_id',     // Foreign key on team_members table
            'id',          // Foreign key on users table
            'id',          // Local key on teams table
            'user_id'      // Local key on team_members table
        );
    }

    public function owner()
    {
        return $this->hasOne(User::class, 'id', 'owned_by');
    }

    public function getSetting($key)
    {
        return $this->settings[$key] ?? null;
    }

    public function setSetting($key, $value)
    {
        $settings = $this->settings ?? [];
        $settings[$key] = $value;
        $this->settings = $settings;
        $this->save();
    }
}
