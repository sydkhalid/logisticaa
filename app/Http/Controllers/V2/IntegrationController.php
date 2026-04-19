<?php

namespace App\Http\Controllers\V2;

use Illuminate\Http\Request;

class IntegrationController extends BaseController
{
    public function __construct(\App\Services\V2\ExternalLogisticsService $integrations)
    {
        parent::__construct($integrations);
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        $health = $this->integrations->integrationHealth($request->user());
        $summary = [
            'online' => 0,
            'warning' => 0,
            'offline' => 0,
        ];

        foreach ($health as $item) {
            $status = $item['status'] ?? 'offline';
            if (!array_key_exists($status, $summary)) {
                $status = 'offline';
            }

            $summary[$status] += 1;
        }

        return $this->render('integrations.index', [
            'pageTitle' => 'Integration Health',
            'pageDescription' => 'Live reachability, token status, and local coverage checks for Travis, FleetX, and WheelsEye.',
            'health' => $health,
            'summary' => $summary,
            'checkedAt' => date('d M Y, h:i A'),
        ]);
    }

    public function refreshFleetToken(Request $request)
    {
        try {
            $token = $this->integrations->refreshFleetToken($request->user());

            if (!$token) {
                throw new \RuntimeException('FleetX token refresh did not return a token.');
            }

            return redirect()->route('v2.integrations.index')
                ->with('message', 'FleetX token refreshed and saved to shared settings.')
                ->with('message_type', 'success');
        } catch (\Throwable $exception) {
            $this->logHandledException($exception, 'FleetX Token Refresh Failed', $request, [], 'warning');
            return redirect()->route('v2.integrations.index')
                ->with('message', $exception->getMessage())
                ->with('message_type', 'danger');
        }
    }

    public function refreshTravisToken(Request $request)
    {
        try {
            $token = $this->integrations->syncSystemBocshToken($request->user());

            if (!$token) {
                throw new \RuntimeException('Travis token refresh did not return a token.');
            }

            return redirect()->route('v2.integrations.index')
                ->with('message', 'Travis token refreshed for the system login and current user session.')
                ->with('message_type', 'success');
        } catch (\Throwable $exception) {
            $this->logHandledException($exception, 'Travis Token Refresh Failed', $request, [], 'warning');
            return redirect()->route('v2.integrations.index')
                ->with('message', $exception->getMessage())
                ->with('message_type', 'danger');
        }
    }
}
