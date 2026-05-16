<?php

namespace App\Services;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;

class StoredTokenService
{
    private const PREFIX = 'enc:';

    public static function encrypt(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return $value;
        }

        if (self::isEncrypted($value)) {
            return $value;
        }

        return self::PREFIX . Crypt::encryptString($value);
    }

    public static function decrypt(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return $value;
        }

        if (!self::isEncrypted($value)) {
            return $value;
        }

        try {
            return Crypt::decryptString(substr($value, strlen(self::PREFIX)));
        } catch (DecryptException $exception) {
            return null;
        }
    }

    public static function isEncrypted(?string $value): bool
    {
        return is_string($value) && strpos($value, self::PREFIX) === 0;
    }
}
