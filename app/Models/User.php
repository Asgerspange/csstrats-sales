<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\CsStrats\Team;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
class User extends Authenticatable
{
    protected $connection = 'mysql';
    use HasFactory, Notifiable;

    protected $fillable = [
        'uuid',
        'name',
        'email',
        'auth_token',
        'teamMember',
        'email_verified_at',
        'steam_id',
        'user_code',
        'google_id',
        'userInfo',
        'country_code',
        'id',
        'stripe_id',
        'password',
        'is_admin',
        'created_at',
        'perm',
        'settings'
    ];

    protected $hidden = [
        'subscriptions',
        'password',
        'updated_at',
        'pm_type',
        'pm_last_four',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // only keep boolean accessor
    protected $appends = ['has_granted_access'];

    public function getHasGrantedAccessAttribute(): bool
    {
        return $this->grantedAccess()->exists();
    }

    public function grantedAccess()
    {
        return $this->hasOne(GrantedAccess::class, 'user_id', 'id')
            ->where(function ($query) {
                $query->where('expires_at', '>', now())
                      ->orWhereNull('expires_at');
            });
    }

    public function ownedTeams()
    {
        return $this->hasMany(Team::class, 'owned_by', 'id');
    }
}
