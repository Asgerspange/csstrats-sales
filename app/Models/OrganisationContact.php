<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrganisationContact extends Model
{
    public $timestamps = false;
    protected $fillable = ['organisation_id', 'contact_id', 'cus_id', 'is_primary'];

    public function organisation()
    {
        return $this->belongsTo(Organisation::class);
    }

    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'cus_id', 'id');
    }
}
