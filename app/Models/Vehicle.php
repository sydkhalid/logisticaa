<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Vehicle extends Model
{
    public function trackings()
    {
        return $this->hasMany(Tracking::class);
    }

    public function activeTrackings()
    {
        return $this->hasMany(Tracking::class)->where('status', 0);
    }
}
