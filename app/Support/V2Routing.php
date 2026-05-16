<?php

namespace App\Support;

class V2Routing
{
    public static function routePrefix(): string
    {
        $configuredPrefix = env('V2_ROUTE_PREFIX');
        if ($configuredPrefix !== null) {
            return trim((string) $configuredPrefix, '/');
        }

        if (defined('LOGISTICAA_V2_FRONT_CONTROLLER') && LOGISTICAA_V2_FRONT_CONTROLLER) {
            return '';
        }

        if (app()->runningInConsole()) {
            return 'v2';
        }

        $request = request();
        $baseUrl = trim((string) $request->getBaseUrl(), '/');
        $scriptName = str_replace('\\', '/', (string) $request->server('SCRIPT_NAME', ''));

        if ($baseUrl === 'v2' || str_ends_with($scriptName, '/v2/index.php')) {
            return '';
        }

        return 'v2';
    }

    public static function usesBasePath(): bool
    {
        return self::routePrefix() === '';
    }

    public static function asset(string $path): string
    {
        $path = ltrim($path, '/');
        $prefix = self::routePrefix();

        return asset($prefix === '' ? $path : $prefix . '/' . $path);
    }
}
