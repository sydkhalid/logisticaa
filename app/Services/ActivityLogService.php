<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class ActivityLogService
{
    protected static $logsTableAvailable;

    public function logRequest(Request $request, ?Response $response = null, ?\Throwable $exception = null): void
    {
        if (!$this->shouldLogRequest($request, $response, $exception)) {
            return;
        }

        $statusCode = $response ? (int) $response->getStatusCode() : $this->exceptionStatus($exception);
        $user = $request->user();

        $this->write([
            'user_id' => $user ? $user->id : null,
            'type' => $this->requestType($statusCode, $exception),
            'title' => $this->requestTitle($request),
            'description' => $this->requestDescription($request, $statusCode, $exception),
            'uri' => $this->maskSensitiveUrl($request->fullUrl()),
            'ip' => $request->ip(),
            'is_api' => $this->isApiRequest($request) ? 1 : 0,
            'request_info' => $this->encodeRequestInfo($request, $statusCode, $exception),
            'created_at' => date('Y-m-d H:i:s'),
            'created_by' => $this->createdBy($user),
        ]);
    }

    public function logSystem(
        string $type,
        string $title,
        string $description,
        array $context = [],
        ?int $userId = null,
        ?string $createdBy = null,
        bool $isApi = false
    ): void {
        $this->write([
            'user_id' => $userId,
            'type' => $this->normalizeType($type),
            'title' => $this->limitText($title, 255),
            'description' => $description,
            'uri' => isset($context['uri']) ? (string) $context['uri'] : 'system://console',
            'ip' => isset($context['ip']) ? (string) $context['ip'] : null,
            'is_api' => $isApi ? 1 : 0,
            'request_info' => json_encode($this->sanitizeValue($context), JSON_UNESCAPED_SLASHES),
            'created_at' => date('Y-m-d H:i:s'),
            'created_by' => $this->limitText($createdBy ?: 'System', 55),
        ]);
    }

    public function logThrowable(
        Throwable $exception,
        string $title = 'Unhandled Exception',
        array $context = [],
        ?Request $request = null,
        ?int $userId = null,
        ?string $createdBy = null,
        ?string $type = null
    ): void {
        if (!$this->logsTableAvailable()) {
            return;
        }

        if ($request === null && app()->bound('request') && !app()->runningInConsole()) {
            $request = app('request');
        }

        $requestUser = $request && method_exists($request, 'user') ? $request->user() : null;
        $payload = [
            'user_id' => $userId ?: ($requestUser ? $requestUser->id : null),
            'type' => $this->normalizeType($type ?: 'danger'),
            'title' => $this->limitText($title, 255),
            'description' => $this->limitText($this->maskSensitiveText($exception->getMessage()), 16000),
            'uri' => $this->maskSensitiveUrl($request ? $request->fullUrl() : (isset($context['uri']) ? (string) $context['uri'] : 'system://exception')),
            'ip' => $request ? $request->ip() : (isset($context['ip']) ? (string) $context['ip'] : null),
            'is_api' => $request ? ($this->isApiRequest($request) ? 1 : 0) : (!empty($context['is_api']) ? 1 : 0),
            'request_info' => json_encode($this->exceptionContext($exception, $context, $request), JSON_UNESCAPED_SLASHES),
            'created_at' => date('Y-m-d H:i:s'),
            'created_by' => $this->limitText($createdBy ?: $this->createdBy($requestUser), 55),
        ];

        $this->write($payload);
    }

    protected function write(array $payload): void
    {
        if (!$this->logsTableAvailable()) {
            return;
        }

        try {
            DB::table('logs')->insert($payload);
        } catch (\Throwable $exception) {
            // Never block the request flow because audit logging failed.
        }
    }

    protected function shouldLogRequest(Request $request, ?Response $response = null, ?\Throwable $exception = null): bool
    {
        if (!$this->logsTableAvailable()) {
            return false;
        }

        if ($request->is('_ignition/*') || $request->is('favicon.ico')) {
            return false;
        }

        if ($request->routeIs('v2.logs.clear')) {
            return false;
        }

        if ($request->isMethod('GET') && ($request->ajax() || $request->expectsJson())) {
            return false;
        }

        if ($response && $response->getStatusCode() === 304) {
            return false;
        }

        return true;
    }

    protected function requestType(int $statusCode, ?\Throwable $exception = null): string
    {
        if ($exception || $statusCode >= 500) {
            return 'danger';
        }

        if ($statusCode >= 400) {
            return 'warning';
        }

        if ($statusCode >= 300) {
            return 'info';
        }

        return in_array($statusCode, [200, 201, 204], true) ? 'success' : 'info';
    }

    protected function requestTitle(Request $request): string
    {
        $route = $request->route();
        $routeName = $route && $route->getName() ? $route->getName() : null;

        if ($routeName) {
            $name = preg_replace('/^v2\./', '', $routeName);
            $name = str_replace(['.', '-'], ' ', $name);

            return $this->limitText(ucwords(trim($name)), 255);
        }

        return $this->limitText(strtoupper($request->method()) . ' ' . trim($request->path(), '/'), 255);
    }

    protected function requestDescription(Request $request, int $statusCode, ?\Throwable $exception = null): string
    {
        $summary = strtoupper($request->method()) . ' ' . $request->path() . ' completed with status ' . $statusCode . '.';

        if ($exception) {
            return $summary . ' ' . $this->maskSensitiveText($exception->getMessage());
        }

        return $summary;
    }

    protected function encodeRequestInfo(Request $request, int $statusCode, ?\Throwable $exception = null): string
    {
        $route = $request->route();
        $data = [
            'method' => $request->method(),
            'path' => $request->path(),
            'full_url' => $this->maskSensitiveUrl($request->fullUrl()),
            'route_name' => $route && $route->getName() ? $route->getName() : null,
            'status_code' => $statusCode,
            'is_ajax' => $request->ajax(),
            'is_api' => $this->isApiRequest($request),
            'query' => $this->sanitizeValue($request->query()),
            'input' => $this->sanitizeValue($request->except(['_token', '_method'])),
            'user_agent' => $request->userAgent(),
            'referer' => $this->maskSensitiveUrl($request->headers->get('referer')),
        ];

        if ($request->allFiles()) {
            $data['files'] = $this->sanitizeValue($request->allFiles());
        }

        if ($exception) {
            $data['exception'] = [
                'class' => get_class($exception),
                'message' => $this->maskSensitiveText($exception->getMessage()),
            ];
        }

        return json_encode($data, JSON_UNESCAPED_SLASHES);
    }

    protected function sanitizeValue($value, ?string $key = null)
    {
        if ($value instanceof UploadedFile) {
            return [
                'name' => $value->getClientOriginalName(),
                'size' => $value->getSize(),
                'mime' => $value->getClientMimeType(),
            ];
        }

        if (is_array($value)) {
            $sanitized = [];

            foreach ($value as $childKey => $childValue) {
                $sanitized[$childKey] = $this->sanitizeValue($childValue, (string) $childKey);
            }

            return $sanitized;
        }

        if ($key && preg_match('/password|token|secret|authorization|bearer|access|epod/i', $key)) {
            return '[masked]';
        }

        if (is_object($value)) {
            return '[object:' . get_class($value) . ']';
        }

        if (is_string($value)) {
            return $this->limitText($value, 1000);
        }

        return $value;
    }

    protected function createdBy($user): string
    {
        if (!$user) {
            return 'Guest';
        }

        $name = $user->name ?: $user->email ?: ('User #' . $user->id);

        return $this->limitText($name, 55);
    }

    protected function normalizeType(string $type): string
    {
        $normalized = strtolower(trim($type));
        $allowed = ['info', 'warning', 'success', 'danger', 'emergency'];

        return in_array($normalized, $allowed, true) ? $normalized : 'info';
    }

    protected function isApiRequest(Request $request): bool
    {
        return $request->is('api/*') || $request->expectsJson();
    }

    protected function exceptionStatus(?\Throwable $exception): int
    {
        if ($exception && method_exists($exception, 'getStatusCode')) {
            return (int) $exception->getStatusCode();
        }

        return 500;
    }

    protected function limitText(?string $value, int $limit): ?string
    {
        if ($value === null) {
            return null;
        }

        return mb_strlen($value) > $limit
            ? mb_substr($value, 0, $limit - 3) . '...'
            : $value;
    }

    protected function logsTableAvailable(): bool
    {
        if (static::$logsTableAvailable !== null) {
            return static::$logsTableAvailable;
        }

        try {
            static::$logsTableAvailable = Schema::hasTable('logs');
        } catch (\Throwable $exception) {
            static::$logsTableAvailable = false;
        }

        return static::$logsTableAvailable;
    }

    protected function exceptionContext(Throwable $exception, array $context = [], ?Request $request = null): array
    {
        $data = [
            'exception' => [
                'class' => get_class($exception),
                'message' => $this->maskSensitiveText($exception->getMessage()),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
            ],
            'context' => $this->sanitizeValue($context),
        ];

        if ($request) {
            $data['request'] = [
                'method' => $request->method(),
                'path' => $request->path(),
                'full_url' => $this->maskSensitiveUrl($request->fullUrl()),
                'route_name' => $request->route() && $request->route()->getName() ? $request->route()->getName() : null,
                'query' => $this->sanitizeValue($request->query()),
                'input' => $this->sanitizeValue($request->except(['_token', '_method'])),
            ];
        }

        return $data;
    }

    protected function maskSensitiveUrl(?string $url): ?string
    {
        if ($url === null || $url === '') {
            return $url;
        }

        return preg_replace_callback(
            '/([?&][^=&]*(?:password|token|secret|authorization|bearer|access|epod)[^=]*)=([^&]*)/i',
            function (array $matches) {
                return $matches[1] . '=[masked]';
            },
            $url
        );
    }

    protected function maskSensitiveText(string $value): string
    {
        return preg_replace_callback(
            '/([?&][^=&]*(?:password|token|secret|authorization|bearer|access|epod)[^=]*)=([^&\\s]*)/i',
            function (array $matches) {
                return $matches[1] . '=[masked]';
            },
            $value
        );
    }
}
