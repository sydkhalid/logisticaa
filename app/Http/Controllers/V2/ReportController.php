<?php

namespace App\Http\Controllers\V2;

use App\Models\Epod;
use App\Models\Tracking;
use App\Models\Vehicle;
use App\Models\Weight;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;

class ReportController extends BaseController
{
    public function __construct(\App\Services\V2\ExternalLogisticsService $integrations)
    {
        parent::__construct($integrations);
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        $filters = $this->validatedFilters($request);
        list($start, $end) = $this->dateRange($filters['from'], $filters['to']);

        $trackingsQuery = $this->applyRange(Tracking::query(), $start, $end);
        $epodsQuery = $this->applyRange(Epod::query()->where('status', 1), $start, $end);
        $weightsEnabled = Schema::hasTable('weights');
        $weightsQuery = $weightsEnabled
            ? $this->applyRange(Weight::query(), $start, $end)
            : null;

        $trackingStatusBreakdown = (clone $trackingsQuery)
            ->selectRaw("CASE WHEN lrStatus IS NULL OR lrStatus = '' THEN 'Unknown' ELSE lrStatus END AS label, COUNT(*) AS aggregate")
            ->groupBy('label')
            ->orderByDesc('aggregate')
            ->limit(6)
            ->get();

        return $this->render('reports.index', [
            'pageTitle' => 'Reports',
            'pageDescription' => 'Operational reporting across vehicles, LR tracking, EPOD uploads, and weight corrections.',
            'filters' => $filters,
            'summary' => [
                'trackingsCreated' => (clone $trackingsQuery)->count(),
                'trackingsActive' => (clone $trackingsQuery)->where('status', 0)->count(),
                'trackingsClosed' => (clone $trackingsQuery)->whereIn('status', [1, 3])->count(),
                'epodsUploaded' => (clone $epodsQuery)->count(),
                'weightCorrections' => $weightsQuery ? (clone $weightsQuery)->count() : 0,
                'pendingLocationSyncs' => Tracking::query()
                    ->where('status', 0)
                    ->where(function ($query) {
                        $query->whereNull('latitude')->orWhere('latitude', '');
                    })
                    ->count(),
            ],
            'fleetSummary' => [
                'totalVehicles' => Vehicle::query()->count(),
                'ownVehicles' => Vehicle::query()->where('vehicleStatus', 0)->count(),
                'marketVehicles' => Vehicle::query()->where('vehicleStatus', 1)->count(),
                'stoppedMarketVehicles' => Vehicle::query()
                    ->where('vehicleStatus', 1)
                    ->where('statusStop', 1)
                    ->count(),
                'fleetAnalytics' => $this->integrations->cachedFleetAnalytics(),
            ],
            'trackingStatusBreakdown' => $trackingStatusBreakdown,
            'recentTrackings' => (clone $trackingsQuery)->latest('id')->limit(10)->get(),
            'recentWeights' => $weightsQuery ? (clone $weightsQuery)->latest('id')->limit(10)->get() : collect(),
            'recentEpods' => (clone $epodsQuery)->latest('id')->limit(10)->get(),
            'weightsEnabled' => $weightsEnabled,
        ]);
    }

    public function export(Request $request, $dataset)
    {
        $filters = $this->validatedFilters($request);
        list($start, $end) = $this->dateRange($filters['from'], $filters['to']);
        $dateSuffix = $filters['from'] . '_to_' . $filters['to'];

        if ($dataset === 'trackings') {
            $filename = 'v2-trackings-' . $dateSuffix . '.csv';
            $headings = ['ID', 'Vehicle No', 'LSP ID', 'LR Number', 'LR Status', 'Stage', 'Latitude', 'Longitude', 'Location', 'Created At', 'Updated At'];
            $rows = $this->applyRange(Tracking::query()->latest('id'), $start, $end)->get();

            return $this->csvDownload($filename, $headings, $rows, function ($tracking) {
                return [
                    $tracking->id,
                    $tracking->vehicleNo,
                    $tracking->lspId,
                    $tracking->lrNumber,
                    $tracking->lrStatus,
                    $this->trackingStage($tracking),
                    $tracking->latitude,
                    $tracking->longitude,
                    $tracking->location,
                    $tracking->created_at,
                    $tracking->updated_at,
                ];
            });
        }

        if ($dataset === 'epods') {
            $filename = 'v2-epods-' . $dateSuffix . '.csv';
            $headings = ['ID', 'LSP ID', 'LR Number', 'File', 'Status', 'Created At'];
            $rows = $this->applyRange(Epod::query()->where('status', 1)->latest('id'), $start, $end)->get();

            return $this->csvDownload($filename, $headings, $rows, function ($epod) {
                return [
                    $epod->id,
                    $epod->lspId,
                    $epod->lrNumber,
                    $epod->epod,
                    (int) $epod->status === 1 ? 'Uploaded' : 'Pending',
                    $epod->created_at,
                ];
            });
        }

        if ($dataset === 'vehicles') {
            $filename = 'v2-vehicles-' . $dateSuffix . '.csv';
            $headings = ['ID', 'Vehicle No', 'Type', 'Mobile No', 'SIM Provider', 'Expiry Date', 'Stopped', 'Created At'];
            $rows = $this->applyRange(Vehicle::query()->latest('id'), $start, $end)->get();

            return $this->csvDownload($filename, $headings, $rows, function ($vehicle) {
                return [
                    $vehicle->id,
                    $vehicle->vehicleNo,
                    (int) $vehicle->vehicleStatus === 1 ? 'Market' : 'Own',
                    $vehicle->mobileNo,
                    $vehicle->simProvider,
                    $vehicle->expireDate,
                    (int) $vehicle->statusStop === 1 ? 'Yes' : 'No',
                    $vehicle->created_at,
                ];
            });
        }

        if ($dataset === 'weights') {
            abort_unless(Schema::hasTable('weights'), 404);

            $filename = 'v2-weights-' . $dateSuffix . '.csv';
            $headings = ['ID', 'LSP ID', 'LR Number', 'Corrected Weight', 'Length', 'Breadth', 'Height', 'Created At'];
            $rows = $this->applyRange(Weight::query()->latest('id'), $start, $end)->get();

            return $this->csvDownload($filename, $headings, $rows, function ($weight) {
                return [
                    $weight->id,
                    $weight->lspId,
                    $weight->lrNumber,
                    $weight->correctedWeight,
                    $weight->length,
                    $weight->breadth,
                    $weight->height,
                    $weight->created_at,
                ];
            });
        }

        abort(404);
    }

    private function validatedFilters(Request $request)
    {
        $validated = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        return [
            'from' => $validated['from'] ?? date('Y-m-01'),
            'to' => $validated['to'] ?? date('Y-m-d'),
        ];
    }

    private function dateRange($from, $to)
    {
        return [
            Carbon::parse($from)->startOfDay(),
            Carbon::parse($to)->endOfDay(),
        ];
    }

    private function applyRange($query, Carbon $start, Carbon $end)
    {
        return $query->whereBetween('created_at', [
            $start->format('Y-m-d H:i:s'),
            $end->format('Y-m-d H:i:s'),
        ]);
    }

    private function csvDownload($filename, array $headings, $rows, callable $mapper)
    {
        return response()->streamDownload(function () use ($headings, $rows, $mapper) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, array_map([$this, 'csvCell'], $headings));

            foreach ($rows as $row) {
                fputcsv($handle, array_map([$this, 'csvCell'], $mapper($row)));
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    private function csvCell($value): string
    {
        if ($value instanceof \DateTimeInterface) {
            $value = $value->format('Y-m-d H:i:s');
        }

        $value = (string) $value;

        return preg_match('/^[=+\-@]/', $value) === 1
            ? "'" . $value
            : $value;
    }

    public function trackingStage($tracking)
    {
        if ((int) $tracking->status === 3) {
            return 'EPOD Uploaded';
        }

        if ((int) $tracking->status === 1) {
            return 'Delivered';
        }

        return 'Active';
    }
}
