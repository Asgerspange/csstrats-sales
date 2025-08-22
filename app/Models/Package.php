<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Package extends Model
{
    use HasFactory, SoftDeletes;
    protected $connection = 'mysql';
    protected $table = 'products';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'price',
        'percent',
        'monthly_price',
        'stripe_price_id',
        'prod_id',
        'description',
        'small_description',
        'features',
        'max_members',
        'max_stratbooks',
        'max_teams',
        'banner',
        'hide',
        'is_custom',
        'organisation_id',
        'is_tracked',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'price' => 'decimal:2',
        'monthly_price' => 'decimal:2',
        'percent' => 'float',
        'max_members' => 'integer',
        'max_stratbooks' => 'integer',
        'max_teams' => 'integer',
        'hide' => 'boolean',
        'organisation_id' => 'integer',
        'is_custom' => 'boolean',
        'features' => 'json',
        'is_tracked' => 'boolean',
    ];

    /**
     * Check if the product is a custom package
     * 
     * @return bool
     */
    public function isCustomPackage()
    {
        return $this->hide === true;
    }

    /**
     * Get all visible packages
     * 
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getVisiblePackages()
    {
        return self::where('hide', 0)->get();
    }

    /**
     * Get all custom packages
     * 
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getCustomPackages()
    {
        return self::where('is_custom', 1)->get();
    }
    
    /**
     * Get all non custom packages
     * 
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getNonCustomPackages()
    {
        return self::where('is_custom', 0)->get();
    }

    /**
     * Get all tracked packages
     */
    public static function getTrackedPackages()
    {
        return self::where('is_tracked', 1)->get();
    }

    /**
     * Get the organisation that owns the package.
     */
    public function organisation()
    {
        return $this->belongsTo(Organisation::class, 'organisation_id');
    }
}