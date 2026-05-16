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
        $relativePath = $prefix === '' ? $path : $prefix . '/' . $path;
        $filePath = $prefix === ''
            ? base_path('v2/' . $path)
            : public_path($relativePath);

        return self::withVersion(asset($relativePath), $filePath);
    }

    public static function publicAsset(string $path): string
    {
        $path = ltrim($path, '/');
        $assetUrl = config('app.asset_url');

        if ($assetUrl) {
            return self::withVersion(rtrim($assetUrl, '/') . '/' . $path, public_path($path));
        }

        if (app()->runningInConsole()) {
            return self::withVersion(asset($path), public_path($path));
        }

        return self::withVersion(request()->getSchemeAndHttpHost() . '/' . $path, public_path($path));
    }

    private static function withVersion(string $url, string $filePath): string
    {
        if (!is_file($filePath)) {
            return $url;
        }

        $separator = str_contains($url, '?') ? '&' : '?';

        return $url . $separator . 'v=' . filemtime($filePath);
    }
}
