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

        return $this->render('reports.index', array_merge([
            'pageTitle' => 'Reports',
            'pageDescription' => 'Operational reporting across vehicles, LR tracking, EPOD uploads, and weight corrections.',
        ], $this->reportData($filters)));
    }

    public function print(Request $request)
    {
        $filters = $this->validatedFilters($request);

        return view('v2.reports.print', array_merge([
            'settings' => $this->settings,
            'appName' => $this->settings && $this->settings->name
                ? $this->settings->name
                : config('app.name', 'Logisticaa'),
            'pageTitle' => 'Operational Report',
            'generatedAt' => now()->format('Y-m-d H:i:s'),
        ], $this->reportData($filters)));
    }

    public function export(Request $request, $dataset)
    {
        $filters = $this->validatedFilters($request);
        list($start, $end) = $this->dateRange($filters['from'], $filters['to']);
        $dateSuffix = $filters['from'] . '_to_' . $filters['to'];
        $format = strtolower((string) $request->query('format', 'csv'));

        abort_unless(in_array($format, ['csv', 'xls'], true), 404);
        $export = $this->exportPayload($dataset, $start, $end, $dateSuffix);

        if ($format === 'xls') {
            return $this->xlsDownload(
                $export['filename_base'] . '.xls',
                $export['title'],
                $filters,
                $export['headings'],
                $export['rows']
            );
        }

        return $this->csvDownload($export['filename_base'] . '.csv', $export['headings'], $export['rows']);
    }

    private function reportData(array $filters): array
    {
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

        return [
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
        ];
    }

    private function exportPayload(string $dataset, Carbon $start, Carbon $end, string $dateSuffix): array
    {
        if ($dataset === 'trackings') {
            $rows = $this->applyRange(Tracking::query()->latest('id'), $start, $end)->get();

            return [
                'title' => 'LR Trackings',
                'filename_base' => 'v2-trackings-' . $dateSuffix,
                'headings' => ['ID', 'Vehicle No', 'LSP ID', 'LR Number', 'LR Status', 'Stage', 'EDD', 'Delivered At', 'Weight', 'Packages', 'Latitude', 'Longitude', 'Location', 'Created At', 'Updated At'],
                'rows' => $this->mapRows($rows, function ($tracking) {
                    return [
                        $tracking->id,
                        $tracking->vehicleNo,
                        $tracking->lspId,
                        $tracking->lrNumber,
                        $tracking->lrStatus,
                        $this->trackingStage($tracking),
                        $tracking->edd,
                        $tracking->actualDeliveredDate,
                        $tracking->actualWeight,
                        $tracking->numberOfPackages,
                        $tracking->latitude,
                        $tracking->longitude,
                        $tracking->location,
                        $tracking->created_at,
                        $tracking->updated_at,
                    ];
                }),
            ];
        }

        if ($dataset === 'epods') {
            $rows = $this->applyRange(Epod::query()->where('status', 1)->latest('id'), $start, $end)->get();

            return [
                'title' => 'EPOD Uploads',
                'filename_base' => 'v2-epods-' . $dateSuffix,
                'headings' => ['ID', 'LSP ID', 'LR Number', 'File', 'Status', 'Created At'],
                'rows' => $this->mapRows($rows, function ($epod) {
                    return [
                        $epod->id,
                        $epod->lspId,
                        $epod->lrNumber,
                        $epod->epod,
                        (int) $epod->status === 1 ? 'Uploaded' : 'Pending',
                        $epod->created_at,
                    ];
                }),
            ];
        }

        if ($dataset === 'vehicles') {
            $rows = $this->applyRange(Vehicle::query()->latest('id'), $start, $end)->get();

            return [
                'title' => 'Vehicles',
                'filename_base' => 'v2-vehicles-' . $dateSuffix,
                'headings' => ['ID', 'Vehicle No', 'Type', 'Mobile No', 'SIM Provider', 'Expiry Date', 'Stopped', 'Created At'],
                'rows' => $this->mapRows($rows, function ($vehicle) {
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
                }),
            ];
        }

        if ($dataset === 'weights') {
            abort_unless(Schema::hasTable('weights'), 404);

            $rows = $this->applyRange(Weight::query()->latest('id'), $start, $end)->get();

            return [
                'title' => 'Weight Corrections',
                'filename_base' => 'v2-weights-' . $dateSuffix,
                'headings' => ['ID', 'LSP ID', 'LR Number', 'Corrected Weight', 'Length', 'Breadth', 'Height', 'Created At'],
                'rows' => $this->mapRows($rows, function ($weight) {
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
                }),
            ];
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

    private function mapRows($rows, callable $mapper): array
    {
        return $rows->map(function ($row) use ($mapper) {
            return array_map([$this, 'exportCell'], $mapper($row));
        })->all();
    }

    private function csvDownload($filename, array $headings, array $rows)
    {
        return response()->streamDownload(function () use ($headings, $rows) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, array_map([$this, 'exportCell'], $headings));

            foreach ($rows as $row) {
                fputcsv($handle, $row);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    private function xlsDownload(string $filename, string $title, array $filters, array $headings, array $rows)
    {
        return response()->streamDownload(function () use ($title, $filters, $headings, $rows) {
            echo '<!DOCTYPE html><html><head><meta charset="UTF-8"></head><body>';
            echo '<h2>' . $this->htmlCell($title) . '</h2>';
            echo '<p>From ' . $this->htmlCell($filters['from']) . ' to ' . $this->htmlCell($filters['to']) . '</p>';
            echo '<table border="1"><thead><tr>';

            foreach ($headings as $heading) {
                echo '<th>' . $this->htmlCell($heading) . '</th>';
            }

            echo '</tr></thead><tbody>';

            foreach ($rows as $row) {
                echo '<tr>';
                foreach ($row as $cell) {
                    echo '<td style="mso-number-format:\'\\@\';">' . $this->htmlCell($cell) . '</td>';
                }
                echo '</tr>';
            }

            echo '</tbody></table></body></html>';
        }, $filename, [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
        ]);
    }

    private function exportCell($value): string
    {
        if ($value instanceof \DateTimeInterface) {
            $value = $value->format('Y-m-d H:i:s');
        }

        $value = (string) $value;

        return preg_match('/^[=+\-@]/', $value) === 1
            ? "'" . $value
            : $value;
    }

    private function htmlCell($value): string
    {
        return e($this->exportCell($value));
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
