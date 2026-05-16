<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tracking extends Model
{
    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function epods()
    {
        return $this->hasMany(Epod::class);
    }

    public function weights()
    {
        return $this->hasMany(Weight::class);
    }
}
