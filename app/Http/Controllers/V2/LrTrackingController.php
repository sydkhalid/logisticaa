<?php

namespace App\Http\Controllers\V2;

use App\Jobs\RefreshLrTrackingJob;
use App\Models\Tracking;
use App\Models\Vehicle;
use App\Services\V2\ExternalLogisticsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class LrTrackingController extends BaseController
{
    public function __construct(\App\Services\V2\ExternalLogisticsService $integrations)
    {
        parent::__construct($integrations);
        $this->middleware('auth');
    }

    public function index()
    {
        return $this->render('lr-trackings.index', [
            'pageTitle' => 'Active LR Tracking',
            'showCompleted' => false,
        ]);
    }

    public function completed()
    {
        return $this->render('lr-trackings.index', [
            'pageTitle' => 'Completed LR Tracking',
            'showCompleted' => true,
        ]);
    }

    public function activeData(Request $request)
    {
        return $this->trackingData($request, false);
    }

    public function completedData(Request $request)
    {
        return $this->trackingData($request, true);
    }

    public function create()
    {
        return $this->render('lr-trackings.form', [
            'pageTitle' => 'Create LR Tracking',
            'tracking' => new Tracking(),
            'vehicles' => $this->eligibleVehiclesQuery()
                ->orderByRaw('CASE WHEN vehicleStatus = 0 THEN 0 ELSE 1 END')
                ->orderBy('vehicleNo')
                ->get(),
            'lrStatuses' => array_values(array_diff(ExternalLogisticsService::lrStatuses(), ['Shipment Delivered'])),
            'truckTypes' => ExternalLogisticsService::truckTypes(),
            'truckTonnages' => ExternalLogisticsService::truckTonnages(),
            'defaultLspId' => $this->defaultLspId(),
            'formAction' => route('v2.lr-trackings.store'),
            'formMethod' => 'POST',
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'vehicle_id' => ['required', 'exists:vehicles,id'],
            'lspId' => ['required', 'string'],
            'lrNumber' => ['required', 'string'],
            'lrStatus' => ['required', 'string', Rule::in(array_values(array_diff(ExternalLogisticsService::lrStatuses(), ['Shipment Delivered'])))],
            'pickUpDate' => ['nullable', 'date'],
            'lrDate' => ['nullable', 'date'],
            'edd' => ['required', 'date'],
            'receiverName' => ['nullable', 'string'],
            'deliveredToPerson' => ['nullable', 'string'],
            'actualWeight' => ['required', 'numeric', 'min:0'],
            'numberOfPackages' => ['required', 'numeric', 'min:1'],
            'length' => ['required', 'numeric', 'min:0'],
            'breadth' => ['required', 'numeric', 'min:0'],
            'height' => ['required', 'numeric', 'min:0'],
            'truckType' => ['required', 'string', Rule::in(ExternalLogisticsService::truckTypes())],
            'truckTonnage' => ['required', 'string', Rule::in(ExternalLogisticsService::truckTonnages())],
            'deliveryNotes' => ['nullable', 'string'],
        ]);

        $vehicle = Vehicle::query()->findOrFail($validated['vehicle_id']);
        $availability = $this->ensureVehicleAvailableForTracking($vehicle, $request);

        if (!$availability['approved']) {
            return back()
                ->withInput()
                ->with('message', $availability['message'])
                ->with('message_type', 'warning');
        }

        $alreadyExists = Tracking::query()
            ->where('lspId', $validated['lspId'])
            ->where('vehicleNo', $vehicle->vehicleNo)
            ->where('status', 0)
            ->exists();

        if ($alreadyExists) {
            return redirect()->route('v2.lr-trackings.index')
                ->with('message', 'This vehicle already has an active LR tracking record.')
                ->with('message_type', 'warning');
        }

        $tracking = new Tracking();
        $tracking->lspId = $validated['lspId'];
        $tracking->lrNumber = $validated['lrNumber'];
        $tracking->lrStatus = $validated['lrStatus'];
        $tracking->latitude = null;
        $tracking->longitude = null;
        $tracking->location = null;
        $tracking->pickUpDate = $this->formatDateTime($validated['pickUpDate'] ?? null);
        $tracking->lrDate = $this->formatDateTime($validated['lrDate'] ?? null);
        $tracking->actualDeliveredDate = null;
        $tracking->edd = $this->formatDateTime($validated['edd'] ?? null);
        $tracking->receiverName = $validated['receiverName'] ?? null;
        $tracking->deliveredToPerson = $validated['deliveredToPerson'] ?? null;
        $tracking->actualWeight = $validated['actualWeight'] ?? null;
        $tracking->numberOfPackages = $validated['numberOfPackages'] ?? null;
        $tracking->length = $validated['length'] ?? null;
        $tracking->breadth = $validated['breadth'] ?? null;
        $tracking->height = $validated['height'] ?? null;
        $tracking->truckType = $validated['truckType'] ?? null;
        $tracking->truckTonnage = $validated['truckTonnage'] ?? null;
        $tracking->vehicleNo = $vehicle->vehicleNo;
        if (Schema::hasColumn('trackings', 'vehicle_id')) {
            $tracking->vehicle_id = $vehicle->id;
        }
        $tracking->deliveryNotes = $validated['deliveryNotes'] ?? null;
        $tracking->status = 0;

        if (Schema::hasColumn('trackings', 'vehicle_status')) {
            $tracking->vehicle_status = $vehicle->vehicleStatus;
        }

        try {
            $tracking->save();
        } catch (\Throwable $exception) {
            $this->logHandledException($exception, 'LR Tracking Create Failed', $request, [
                'lrNumber' => $tracking->lrNumber,
                'vehicleNo' => $tracking->vehicleNo,
            ]);

            return back()
                ->withInput()
                ->with('message', $exception->getMessage())
                ->with('message_type', 'danger');
        }

        try {
            $this->queueTrackingRefresh($tracking, $request, 'create');
            $message = 'LR tracking created. Background sync has been queued.';
            $messageType = 'success';
        } catch (\Throwable $exception) {
            $this->logHandledException($exception, 'LR Tracking Sync Queue Failed After Create', $request, [
                'tracking_id' => $tracking->id,
                'lrNumber' => $tracking->lrNumber,
                'vehicleNo' => $tracking->vehicleNo,
            ], 'warning');
            $message = 'LR tracking created, but background sync could not be queued: ' . $exception->getMessage();
            $messageType = 'warning';
        }

        return redirect()->route('v2.lr-trackings.index')
            ->with('message', $message)
            ->with('message_type', $messageType);
    }

    public function show(Tracking $tracking)
    {
        return $this->render('lr-trackings.show', [
            'pageTitle' => 'LR Tracking Details',
            'tracking' => $tracking,
        ]);
    }

    public function edit(Tracking $tracking)
    {
        return $this->render('lr-trackings.edit', [
            'pageTitle' => 'Update LR Status',
            'tracking' => $tracking,
            'lrStatuses' => ExternalLogisticsService::lrStatuses(),
            'formAction' => route('v2.lr-trackings.update', $tracking),
        ]);
    }

    public function update(Request $request, Tracking $tracking)
    {
        $validated = $request->validate([
            'lrStatus' => ['required', 'string', Rule::in(ExternalLogisticsService::lrStatuses())],
            'actualDeliveredDate' => ['nullable', 'date', 'required_if:lrStatus,Shipment Delivered'],
        ]);

        $tracking->lrStatus = $validated['lrStatus'];

        if ($validated['lrStatus'] === 'Shipment Delivered') {
            $tracking->actualDeliveredDate = $this->formatDateTime($validated['actualDeliveredDate'] ?? null);
            $tracking->status = 1;
        } else {
            $tracking->actualDeliveredDate = null;
            $tracking->status = 0;
        }

        try {
            $tracking->save();
        } catch (\Throwable $exception) {
            $this->logHandledException($exception, 'LR Tracking Update Failed', $request, [
                'tracking_id' => $tracking->id,
                'lrNumber' => $tracking->lrNumber,
                'vehicleNo' => $tracking->vehicleNo,
            ]);

            return back()
                ->withInput()
                ->with('message', $exception->getMessage())
                ->with('message_type', 'danger');
        }

        try {
            $this->queueTrackingRefresh($tracking, $request, 'update');
            $message = 'LR status updated. Background sync has been queued.';
            $messageType = 'success';
        } catch (\Throwable $exception) {
            $this->logHandledException($exception, 'LR Tracking Sync Queue Failed After Update', $request, [
                'tracking_id' => $tracking->id,
                'lrNumber' => $tracking->lrNumber,
                'vehicleNo' => $tracking->vehicleNo,
            ], 'warning');
            $message = 'LR status updated, but background sync could not be queued: ' . $exception->getMessage();
            $messageType = 'warning';
        }

        return redirect()->route((int) $tracking->status === 1 ? 'v2.lr-trackings.completed' : 'v2.lr-trackings.index')
            ->with('message', $message)
            ->with('message_type', $messageType);
    }

    public function refresh(Request $request, Tracking $tracking)
    {
        try {
            $this->queueTrackingRefresh($tracking, $request, 'manual-refresh');

            return back()
                ->with('message', 'Tracking refresh has been queued.')
                ->with('message_type', 'success');
        } catch (\Throwable $exception) {
            $this->logHandledException($exception, 'LR Tracking Refresh Queue Failed', $request, [
                'tracking_id' => $tracking->id,
                'lrNumber' => $tracking->lrNumber,
            ]);
            return back()
                ->with('message', $exception->getMessage())
                ->with('message_type', 'danger');
        }
    }

    private function queueTrackingRefresh(Tracking $tracking, Request $request, string $reason): void
    {
        $user = $request->user();

        RefreshLrTrackingJob::dispatch($tracking->id, $user ? $user->id : null, $reason);
    }

    public function checkVehicleAvailability(Request $request)
    {
        $validated = $request->validate([
            'vehicle_id' => ['required', 'exists:vehicles,id'],
        ]);

        $vehicle = Vehicle::query()->findOrFail($validated['vehicle_id']);

        try {
            return response()->json($this->evaluateVehicleAvailability($vehicle, $request->user()));
        } catch (\Throwable $exception) {
            $this->logHandledException($exception, 'LR Vehicle Availability Check Failed', $request, [
                'vehicle_id' => $vehicle->id,
                'vehicleNo' => $vehicle->vehicleNo,
            ], 'warning');
            return response()->json([
                'approved' => false,
                'message' => $exception->getMessage(),
            ], 503);
        }
    }

    private function trackingData(Request $request, bool $completed)
    {
        $query = Tracking::query()
            ->select(['id', 'vehicleNo', 'lspId', 'lrNumber', 'lrDate', 'lrStatus', 'status', 'created_at'])
            ->when($completed, function ($builder) {
                $builder->whereIn('status', [1, 3]);
            }, function ($builder) {
                $builder->where('status', 0);
            })
            ->latest('created_at');

        return $this->datatableResponse(
            $request,
            $query,
            ['vehicleNo', 'lspId', 'lrNumber', 'lrStatus', 'lrDate'],
            ['created_at', 'vehicleNo', 'lspId', 'lrNumber', 'lrDate', 'status', null],
            function (Tracking $tracking, int $index) use ($completed) {
                $statusLabel = $completed && (int) $tracking->status === 3 ? 'EPOD Uploaded' : $tracking->lrStatus;
                $statusBadge = '<span class="badge badge-' . (in_array((int) $tracking->status, [1, 3], true) ? 'success' : 'warning') . '">'
                    . e($statusLabel)
                    . '</span>';

                $actions = [];

                if (!$completed) {
                    $actions[] = $this->actionLink(route('v2.lr-trackings.edit', $tracking), 'Edit', 'btn-outline-primary');
                }

                $actions[] = $this->actionForm(route('v2.lr-trackings.refresh', $tracking), 'Refresh', 'btn-outline-success');
                $actions[] = $this->actionLink(route('v2.lr-trackings.show', $tracking), 'View', 'btn-outline-info');

                return [
                    'index' => $index,
                    'vehicleNo' => e($tracking->vehicleNo),
                    'lspId' => e($tracking->lspId),
                    'lrNumber' => e($tracking->lrNumber),
                    'lrDate' => e($tracking->lrDate ?: '-'),
                    'status' => $statusBadge,
                    'actions' => $this->actionGroup($actions),
                ];
            }
        );
    }

    private function eligibleVehiclesQuery()
    {
        return Vehicle::query()->where(function ($builder) {
            $builder->where('vehicleStatus', 0)
                ->orWhere(function ($market) {
                    $market->where('vehicleStatus', 1)
                        ->where(function ($status) {
                            $status->whereNull('statusStop')
                                ->orWhere('statusStop', 0);
                        });
                });
        });
    }

    private function ensureVehicleAvailableForTracking(Vehicle $vehicle, Request $request)
    {
        try {
            return $this->evaluateVehicleAvailability($vehicle, $request->user());
        } catch (\Throwable $exception) {
            $this->logHandledException($exception, 'LR Vehicle Availability Check Failed', $request, [
                'vehicle_id' => $vehicle->id,
                'vehicleNo' => $vehicle->vehicleNo,
            ], 'warning');

            return [
                'approved' => false,
                'message' => $exception->getMessage(),
            ];
        }
    }

    private function evaluateVehicleAvailability(Vehicle $vehicle, $user)
    {
        if ((int) $vehicle->vehicleStatus !== 1) {
            return [
                'approved' => true,
                'message' => 'Own vehicle is ready for LR tracking.',
            ];
        }

        if ((int) $vehicle->statusStop === 1) {
            return [
                'approved' => false,
                'message' => 'SIM tracking is stopped for this market vehicle.',
            ];
        }

        $details = $this->integrations->findFleetVehicle($vehicle->vehicleNo, $user);

        return [
            'approved' => (bool) $details,
            'message' => $details
                ? 'FleetX vehicle approval looks active.'
                : 'Vehicle is not yet available from FleetX live analytics.',
        ];
    }
}
