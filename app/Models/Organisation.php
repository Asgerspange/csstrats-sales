<?php

namespace App\Models;

use Illuminate\Database\Eloquent\{
    Model,
    SoftDeletes
};

class Organisation extends Model
{
    use SoftDeletes;
    protected $connection = 'mysql_sales';
    protected $fillable = ['name', 'cvr', 'country', 'address', 'type', 'zip', 'cus_id'];

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'cus_id', 'id');
    }

    public function contacts()
    {
        return $this->hasMany(OrganisationContact::class);
    }

    public function packages()
    {
        return $this->hasMany(Package::class, 'organisation_id', 'id');
    }
}
