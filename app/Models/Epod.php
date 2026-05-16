<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Epod extends Model
{
    public function tracking()
    {
        return $this->belongsTo(Tracking::class);
    }
}
