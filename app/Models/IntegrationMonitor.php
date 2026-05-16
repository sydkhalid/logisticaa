<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IntegrationMonitor extends Model
{
    protected $guarded = [];

    protected $casts = [
        'last_success_at' => 'datetime',
        'last_error_at' => 'datetime',
        'token_refreshed_at' => 'datetime',
    ];
}
