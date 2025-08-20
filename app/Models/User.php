<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    protected $connection = 'mysql';
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
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
    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'subscriptions',
        'password',
        'updated_at',
        'pm_type',
        'pm_last_four',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    protected $appends = ['has_granted_access', 'granted_access'];

    public function getGrantedAccessAttribute(): ?GrantedAccess
    {
        return $this->grantedAccess()->with('grantedBy')->first();
    }

    public function getHasGrantedAccessAttribute(): bool
    {
        return $this->hasGrantedAccess();
    }

    public function hasGrantedAccess(): bool
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
}
