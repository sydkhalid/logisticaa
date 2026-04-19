<?php

namespace App\Http\Middleware;

use App\Services\ActivityLogService;
use Closure;
use Illuminate\Http\Request;

class StoreActivityLog
{
    protected $logs;

    public function __construct(ActivityLogService $logs)
    {
        $this->logs = $logs;
    }

    public function handle(Request $request, Closure $next)
    {
        try {
            $response = $next($request);
        } catch (\Throwable $exception) {
            $this->logs->logRequest($request, null, $exception);
            throw $exception;
        }

        $this->logs->logRequest($request, $response);

        return $response;
    }
}
