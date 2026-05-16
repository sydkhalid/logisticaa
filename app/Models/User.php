<?php

namespace App\Models;

use App\Services\StoredTokenService;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'is_admin',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'access_token',
        'bearer_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'is_admin' => 'boolean',
    ];

    public function getAccessTokenAttribute($value)
    {
        return StoredTokenService::decrypt($value);
    }

    public function setAccessTokenAttribute($value): void
    {
        $this->attributes['access_token'] = StoredTokenService::encrypt($value);
    }

    public function getBearerTokenAttribute($value)
    {
        return StoredTokenService::decrypt($value);
    }

    public function setBearerTokenAttribute($value): void
    {
        $this->attributes['bearer_token'] = StoredTokenService::encrypt($value);
    }

    public function isAdmin(): bool
    {
        if ((bool) ($this->is_admin ?? false)) {
            return true;
        }

        $configuredEmails = config('integrations.logs.admin_emails', 'connect@logisticaa.co.in');
        $adminEmails = array_filter(array_map(function ($email) {
            return strtolower(trim((string) $email));
        }, explode(',', (string) $configuredEmails)));

        return in_array(strtolower((string) $this->email), $adminEmails, true);
    }
}
