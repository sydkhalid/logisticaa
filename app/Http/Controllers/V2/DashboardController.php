<?php

namespace App\Http\Controllers\V2;

use App\Models\ActivityLog;
use App\Models\Epod;
use App\Models\Tracking;
use App\Models\Vehicle;
use App\Models\Weight;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;

class DashboardController extends BaseController
{
    public function __construct(\App\Services\V2\ExternalLogisticsService $integrations)
    {
        parent::__construct($integrations);
        $this->middleware('auth');
    }

    public function index()
    {
        $now = Carbon::now();
        $todayStart = $now->copy()->startOfDay();
        $weekStart = $now->copy()->subDays(6)->startOfDay();
        $weightsEnabled = Schema::hasTable('weights');
        $analytics = $this->integrations->cachedFleetAnalytics();

        $trackingStatusCounts = Tracking::query()
            ->selectRaw('status, COUNT(*) AS aggregate')
            ->groupBy('status')
            ->get()
            ->pluck('aggregate', 'status');

        $vehicleMixCounts = Vehicle::query()
            ->selectRaw('vehicleStatus, COALESCE(statusStop, 0) AS stop_flag, COUNT(*) AS aggregate')
            ->groupBy('vehicleStatus', 'stop_flag')
            ->get();

        $ownVehicles = 0;
        $marketVehicles = 0;
        $activeMarketVehicles = 0;
        $stoppedMarketVehicles = 0;

        foreach ($vehicleMixCounts as $vehicleBucket) {
            if ((int) $vehicleBucket->vehicleStatus === 0) {
                $ownVehicles += (int) $vehicleBucket->aggregate;
                continue;
            }

            $marketVehicles += (int) $vehicleBucket->aggregate;

            if ((int) $vehicleBucket->stop_flag === 1) {
                $stoppedMarketVehicles += (int) $vehicleBucket->aggregate;
            } else {
                $activeMarketVehicles += (int) $vehicleBucket->aggregate;
            }
        }

        $activeTrackingCount = $this->statusCount($trackingStatusCounts, 0);
        $deliveredTrackingCount = $this->statusCount($trackingStatusCounts, 1);
        $epodClosedCount = $this->statusCount($trackingStatusCounts, 3);
        $closedTrackingCount = $deliveredTrackingCount + $epodClosedCount;
        $totalTrackingCount = $activeTrackingCount + $closedTrackingCount;

        $delayedTrackingCount = Tracking::query()
            ->where('status', 0)
            ->whereNotNull('edd')
            ->where('edd', '<', $now->format('Y-m-d H:i:s'))
            ->count();

        $locationGapQuery = $this->activeLocationGapQuery();
        $locationGapCount = (clone $locationGapQuery)->count();
        $issueTrackingCount = Tracking::query()
            ->where(function ($query) use ($now) {
                $query->where('status', 1)
                    ->orWhere(function ($delayed) use ($now) {
                        $delayed->where('status', 0)
                            ->whereNotNull('edd')
                            ->where('edd', '<', $now->format('Y-m-d H:i:s'));
                    })
                    ->orWhere(function ($gap) {
                        $gap->where('status', 0)
                            ->where(function ($location) {
                                $location->whereNull('latitude')
                                    ->orWhere('latitude', '')
                                    ->orWhereNull('longitude')
                                    ->orWhere('longitude', '')
                                    ->orWhereNull('location')
                                    ->orWhere('location', '');
                            });
                    });
            })
            ->distinct()
            ->count('id');

        $epodCount = Epod::query()->where('status', 1)->count();
        $weightCorrectionCount = $weightsEnabled ? Weight::query()->count() : 0;
        $warningLogCount = ActivityLog::query()
            ->whereIn('type', ['warning', 'danger', 'emergency'])
            ->where('created_at', '>=', $todayStart->format('Y-m-d H:i:s'))
            ->count();

        $trackingTrendMap = $this->groupCountByDay(
            Tracking::query(),
            $weekStart,
            $now
        );
        $epodTrendMap = $this->groupCountByDay(
            Epod::query()->where('status', 1),
            $weekStart,
            $now
        );
        $weightTrendMap = $weightsEnabled
            ? $this->groupCountByDay(Weight::query(), $weekStart, $now)
            : collect();

        $trendLabels = [];
        $trackingTrend = [];
        $epodTrend = [];
        $weightTrend = [];

        for ($offset = 6; $offset >= 0; $offset--) {
            $day = $now->copy()->subDays($offset);
            $key = $day->format('Y-m-d');

            $trendLabels[] = $day->format('d M');
            $trackingTrend[] = (int) ($trackingTrendMap[$key] ?? 0);
            $epodTrend[] = (int) ($epodTrendMap[$key] ?? 0);
            $weightTrend[] = (int) ($weightTrendMap[$key] ?? 0);
        }

        $fleetTotal = max((int) ($analytics['totalVehicles'] ?? 0), 0);
        $fleetRunning = max((int) ($analytics['runningVehicles'] ?? 0), 0);
        $fleetParked = max((int) ($analytics['parkedVehicles'] ?? 0), 0);
        $fleetIdle = max((int) ($analytics['idleVehicles'] ?? 0), 0);
        $fleetDisconnected = max((int) ($analytics['disconnectedVehicles'] ?? 0), 0);
        $fleetUtilization = $fleetTotal > 0
            ? round(($fleetRunning * 100) / $fleetTotal, 1)
            : round((float) ($analytics['utilization'] ?? 0), 1);

        $completionRate = $totalTrackingCount > 0
            ? round(($closedTrackingCount * 100) / $totalTrackingCount, 1)
            : 0;
        $epodClosureRate = $closedTrackingCount > 0
            ? round(($epodClosedCount * 100) / $closedTrackingCount, 1)
            : 0;

        $shipmentLabels = ['On-Time Active', 'Delayed', 'Delivered Awaiting EPOD', 'EPOD Uploaded'];
        $shipmentValues = [
            max($activeTrackingCount - $delayedTrackingCount, 0),
            $delayedTrackingCount,
            $deliveredTrackingCount,
            $epodClosedCount,
        ];
        $shipmentColors = ['#0f766e', '#ef4444', '#f59e0b', '#2563eb'];
        $shipmentTotal = array_sum($shipmentValues);
        $shipmentSegments = [];

        foreach ($shipmentLabels as $index => $label) {
            $value = (int) ($shipmentValues[$index] ?? 0);

            $shipmentSegments[] = [
                'label' => $label,
                'value' => $value,
                'color' => $shipmentColors[$index],
                'percentage' => $shipmentTotal > 0 ? round(($value * 100) / $shipmentTotal, 1) : 0,
            ];
        }

        $hasTrendData = array_sum($trackingTrend) + array_sum($epodTrend) + array_sum($weightTrend) > 0;
        $insightGroups = [
            'critical' => [],
            'warning' => [],
            'info' => [],
        ];

        if ($delayedTrackingCount > 0) {
            $insightGroups['critical'][] = [
                'type' => 'critical',
                'title' => 'Delayed shipments need review',
                'description' => $delayedTrackingCount . ' active LR records are already past EDD and should be escalated first.',
                'action_label' => 'Review Active LR',
                'action_url' => route('v2.lr-trackings.index'),
            ];
        }

        if ($deliveredTrackingCount > 0) {
            $insightGroups['warning'][] = [
                'type' => 'warning',
                'title' => 'Delivered LR are still awaiting EPOD',
                'description' => $deliveredTrackingCount . ' delivered records are not yet closed with EPOD upload.',
                'action_label' => 'Upload EPOD',
                'action_url' => route('v2.epods.create'),
            ];
        }

        if ($locationGapCount > 0) {
            $insightGroups['warning'][] = [
                'type' => 'warning',
                'title' => 'Location gaps are affecting live visibility',
                'description' => $locationGapCount . ' active LR records are still missing coordinates or a resolved location.',
                'action_label' => 'Check Integrations',
                'action_url' => route('v2.integrations.index'),
            ];
        }

        if ($stoppedMarketVehicles > 0) {
            $insightGroups['info'][] = [
                'type' => 'info',
                'title' => 'Stopped market vehicles need confirmation',
                'description' => $stoppedMarketVehicles . ' market vehicles currently have SIM tracking stopped.',
                'action_label' => 'Open Market Vehicles',
                'action_url' => route('v2.market-vehicles.index'),
            ];
        }

        if ($fleetDisconnected > 0) {
            $insightGroups['info'][] = [
                'type' => 'info',
                'title' => 'Some live vehicles are disconnected',
                'description' => $fleetDisconnected . ' FleetX vehicles are currently disconnected and may not report live positions.',
                'action_label' => 'Review Integrations',
                'action_url' => route('v2.integrations.index'),
            ];
        }

        if (empty($insightGroups['critical']) && empty($insightGroups['warning']) && empty($insightGroups['info'])) {
            $insightGroups['info'][] = [
                'type' => 'info',
                'title' => 'Operational flow looks healthy',
                'description' => 'No urgent shipment delays, location gaps, or stopped-market backlog were detected in the current dashboard scan.',
                'action_label' => 'Open Reports',
                'action_url' => route('v2.reports.index'),
            ];
        }

        $attentionTabs = [
            [
                'key' => 'delayed',
                'label' => 'Delayed LR',
                'count' => $delayedTrackingCount,
                'description' => 'Past-EDD shipments needing follow-up',
                'action_label' => 'Open Active LR',
                'action_url' => route('v2.lr-trackings.index'),
            ],
            [
                'key' => 'pending-epod',
                'label' => 'Pending EPOD',
                'count' => $deliveredTrackingCount,
                'description' => 'Delivered LR waiting for proof upload',
                'action_label' => 'Upload EPOD',
                'action_url' => route('v2.epods.create'),
            ],
            [
                'key' => 'location-gaps',
                'label' => 'Location Gaps',
                'count' => $locationGapCount,
                'description' => 'Active LR missing resolved location',
                'action_label' => 'Check Integrations',
                'action_url' => route('v2.integrations.index'),
            ],
        ];

        return $this->render('dashboard.index', [
            'pageTitle' => 'Dashboard',
            'pageDescription' => 'Live operations view focused on LR flow, vehicle readiness, EPOD closure, and project analytics.',
            'summary' => [
                'totalTrackings' => $totalTrackingCount,
                'activeTrackingCount' => $activeTrackingCount,
                'closedTrackingCount' => $closedTrackingCount,
                'delayedTrackingCount' => $delayedTrackingCount,
                'pendingEpodCount' => $deliveredTrackingCount,
                'locationGapCount' => $locationGapCount,
                'epodCount' => $epodCount,
                'totalVehicles' => $ownVehicles + $marketVehicles,
                'ownVehicles' => $ownVehicles,
                'marketVehicles' => $marketVehicles,
                'activeMarketVehicles' => $activeMarketVehicles,
                'stoppedMarketVehicles' => $stoppedMarketVehicles,
                'weightCorrectionCount' => $weightCorrectionCount,
                'issueCount' => $issueTrackingCount + $stoppedMarketVehicles,
            ],
            'today' => [
                'trackingsCreated' => Tracking::query()
                    ->whereBetween('created_at', [$todayStart->format('Y-m-d H:i:s'), $now->format('Y-m-d H:i:s')])
                    ->count(),
                'epodsUploaded' => Epod::query()
                    ->where('status', 1)
                    ->whereBetween('created_at', [$todayStart->format('Y-m-d H:i:s'), $now->format('Y-m-d H:i:s')])
                    ->count(),
                'weightCorrections' => $weightsEnabled
                    ? Weight::query()
                        ->whereBetween('created_at', [$todayStart->format('Y-m-d H:i:s'), $now->format('Y-m-d H:i:s')])
                        ->count()
                    : 0,
                'warningsLogged' => $warningLogCount,
            ],
            'performance' => [
                'completionRate' => $completionRate,
                'epodClosureRate' => $epodClosureRate,
                'fleetUtilization' => $fleetUtilization,
                'fleetRunning' => $fleetRunning,
                'fleetTotal' => $fleetTotal,
            ],
            'shipmentSegments' => $shipmentSegments,
            'hasShipmentData' => $shipmentTotal > 0,
            'hasTrendData' => $hasTrendData,
            'shipmentMix' => [
                'labels' => $shipmentLabels,
                'values' => $shipmentValues,
            ],
            'trend' => [
                'labels' => $trendLabels,
                'trackings' => $trackingTrend,
                'epods' => $epodTrend,
                'weights' => $weightTrend,
            ],
            'weightsEnabled' => $weightsEnabled,
            'insightGroups' => $insightGroups,
            'attentionTabs' => $attentionTabs,
            'recentTrackings' => Tracking::query()
                ->latest('updated_at')
                ->limit(8)
                ->get(['id', 'vehicleNo', 'lspId', 'lrNumber', 'lrStatus', 'status', 'location', 'updated_at']),
            'recentAlerts' => ActivityLog::query()
                ->whereIn('type', ['warning', 'danger', 'emergency'])
                ->latest('created_at')
                ->limit(6)
                ->get(['id', 'type', 'title', 'created_at']),
            'reportWindows' => [
                'today' => [
                    'label' => 'Today',
                    'from' => $todayStart->format('Y-m-d'),
                    'to' => $now->format('Y-m-d'),
                ],
                'week' => [
                    'label' => 'Last 7 Days',
                    'from' => $weekStart->format('Y-m-d'),
                    'to' => $now->format('Y-m-d'),
                ],
                'month' => [
                    'label' => 'This Month',
                    'from' => $now->copy()->startOfMonth()->format('Y-m-d'),
                    'to' => $now->format('Y-m-d'),
                ],
            ],
        ]);
    }

    public function attention($panel)
    {
        $panelData = $this->resolveAttentionPanel((string) $panel, Carbon::now());

        if (!$panelData) {
            abort(404);
        }

        return response()->json([
            'panel' => $panelData['key'],
            'count' => $panelData['count'],
            'html' => view('v2.dashboard.partials.attention-panel', [
                'panel' => $panelData['key'],
                'title' => $panelData['title'],
                'description' => $panelData['description'],
                'actionLabel' => $panelData['action_label'],
                'actionUrl' => $panelData['action_url'],
                'records' => $panelData['records'],
                'emptyMessage' => $panelData['empty_message'],
            ])->render(),
        ]);
    }

    private function statusCount($counts, $key)
    {
        return (int) ($counts->get((string) $key, $counts->get($key, 0)));
    }

    private function activeLocationGapQuery()
    {
        return Tracking::query()
            ->where('status', 0)
            ->where(function ($query) {
                $query->whereNull('latitude')
                    ->orWhere('latitude', '')
                    ->orWhereNull('longitude')
                    ->orWhere('longitude', '')
                    ->orWhereNull('location')
                    ->orWhere('location', '');
            });
    }

    private function groupCountByDay($query, Carbon $start, Carbon $end)
    {
        return $query->whereBetween('created_at', [
            $start->format('Y-m-d H:i:s'),
            $end->format('Y-m-d H:i:s'),
        ])
            ->selectRaw('DATE(created_at) AS day, COUNT(*) AS aggregate')
            ->groupBy('day')
            ->get()
            ->pluck('aggregate', 'day');
    }

    private function resolveAttentionPanel(string $panel, Carbon $now): ?array
    {
        if ($panel === 'delayed') {
            $records = Tracking::query()
                ->where('status', 0)
                ->whereNotNull('edd')
                ->where('edd', '<', $now->format('Y-m-d H:i:s'))
                ->orderBy('edd')
                ->limit(10)
                ->get(['id', 'vehicleNo', 'lrNumber', 'lrStatus', 'edd', 'updated_at']);

            return [
                'key' => $panel,
                'title' => 'Delayed LR Queue',
                'description' => 'Past-EDD shipments that need immediate dispatch follow-up.',
                'action_label' => 'Open Active LR',
                'action_url' => route('v2.lr-trackings.index'),
                'empty_message' => 'No delayed LR records are waiting right now.',
                'count' => $records->count(),
                'records' => $records,
            ];
        }

        if ($panel === 'pending-epod') {
            $records = Tracking::query()
                ->where('status', 1)
                ->latest('updated_at')
                ->limit(10)
                ->get(['id', 'vehicleNo', 'lrNumber', 'lrStatus', 'actualDeliveredDate', 'updated_at']);

            return [
                'key' => $panel,
                'title' => 'Pending EPOD Closure',
                'description' => 'Delivered LR that still require proof-of-delivery upload.',
                'action_label' => 'Upload EPOD',
                'action_url' => route('v2.epods.create'),
                'empty_message' => 'No delivered LR are waiting for EPOD upload.',
                'count' => $records->count(),
                'records' => $records,
            ];
        }

        if ($panel === 'location-gaps') {
            $records = $this->activeLocationGapQuery()
                ->latest('updated_at')
                ->limit(10)
                ->get(['id', 'vehicleNo', 'lrNumber', 'lrStatus', 'location', 'updated_at']);

            return [
                'key' => $panel,
                'title' => 'Location Gap Queue',
                'description' => 'Active LR where live coordinates or location names are still missing.',
                'action_label' => 'Check Integrations',
                'action_url' => route('v2.integrations.index'),
                'empty_message' => 'No active LR records are missing location details.',
                'count' => $records->count(),
                'records' => $records,
            ];
        }

        return null;
    }
}
