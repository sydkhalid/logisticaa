<?php

namespace App\Models;

use App\Services\StoredTokenService;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $hidden = [
        'address',
        'access_token',
    ];

    public function getAddressAttribute($value)
    {
        return StoredTokenService::decrypt($value);
    }

    public function setAddressAttribute($value): void
    {
        $this->attributes['address'] = StoredTokenService::encrypt($value);
    }

    public function getAccessTokenAttribute($value)
    {
        return StoredTokenService::decrypt($value);
    }

    public function setAccessTokenAttribute($value): void
    {
        $this->attributes['access_token'] = StoredTokenService::encrypt($value);
    }
}
