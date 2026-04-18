<?php

namespace App\Http\Controllers\V2;

use App\Models\Epod;
use App\Models\Tracking;
use App\Models\Vehicle;

class DashboardController extends BaseController
{
    public function __construct(\App\Services\V2\ExternalLogisticsService $integrations)
    {
        parent::__construct($integrations);
        $this->middleware('auth');
    }

    public function index()
    {
        return $this->render('dashboard.index', [
            'pageTitle' => 'Dashboard',
            'stats' => [
                'vehicleCount' => Vehicle::query()->count(),
                'activeTrackingCount' => Tracking::query()->where('status', '0')->count(),
                'completedTrackingCount' => Tracking::query()->where('status', '1')->count(),
                'epodCount' => Epod::query()->where('status', '1')->count(),
            ],
            'analytics' => $this->integrations->getFleetAnalytics(auth()->user()),
            'recentTrackings' => Tracking::query()->latest('id')->limit(6)->get(),
            'recentVehicles' => Vehicle::query()->latest('id')->limit(6)->get(),
            'recentEpods' => Epod::query()->latest('id')->limit(6)->get(),
        ]);
    }
}
