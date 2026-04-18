<?php

namespace App\Http\Controllers\V2;

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
            'trackings' => Tracking::query()
                ->where('status', '0')
                ->latest('id')
                ->get(),
            'showCompleted' => false,
        ]);
    }

    public function completed()
    {
        return $this->render('lr-trackings.index', [
            'pageTitle' => 'Completed LR Tracking',
            'trackings' => Tracking::query()
                ->whereIn('status', [1, 3])
                ->latest('id')
                ->get(),
            'showCompleted' => true,
        ]);
    }

    public function create()
    {
        return $this->render('lr-trackings.form', [
            'pageTitle' => 'Create LR Tracking',
            'tracking' => new Tracking(),
            'vehicles' => Vehicle::query()->orderBy('vehicleNo')->get(),
            'lrStatuses' => array_values(array_diff(ExternalLogisticsService::lrStatuses(), ['Shipment Delivered'])),
            'truckTypes' => ExternalLogisticsService::truckTypes(),
            'truckTonnages' => ExternalLogisticsService::truckTonnages(),
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
        $tracking->deliveryNotes = $validated['deliveryNotes'] ?? null;
        $tracking->status = 0;

        if (Schema::hasColumn('trackings', 'vehicle_status')) {
            $tracking->vehicle_status = $vehicle->vehicleStatus;
        }

        $tracking->save();

        try {
            $this->integrations->syncTracking($tracking, $request->user());
            $message = 'LR tracking created and synced successfully.';
            $messageType = 'success';
        } catch (\Throwable $exception) {
            $message = 'LR tracking created, but sync failed: ' . $exception->getMessage();
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

        $tracking->save();

        try {
            $this->integrations->syncTracking($tracking, $request->user());
            $message = 'LR status updated successfully.';
            $messageType = 'success';
        } catch (\Throwable $exception) {
            $message = 'LR status updated, but sync failed: ' . $exception->getMessage();
            $messageType = 'warning';
        }

        return redirect()->route((int) $tracking->status === 1 ? 'v2.lr-trackings.completed' : 'v2.lr-trackings.index')
            ->with('message', $message)
            ->with('message_type', $messageType);
    }

    public function refresh(Request $request, Tracking $tracking)
    {
        try {
            $this->integrations->syncTracking($tracking, $request->user());

            return back()
                ->with('message', 'Tracking location refreshed successfully.')
                ->with('message_type', 'success');
        } catch (\Throwable $exception) {
            return back()
                ->with('message', $exception->getMessage())
                ->with('message_type', 'danger');
        }
    }

    public function checkVehicleAvailability(Request $request)
    {
        $validated = $request->validate([
            'vehicle_id' => ['required', 'exists:vehicles,id'],
        ]);

        $vehicle = Vehicle::query()->findOrFail($validated['vehicle_id']);
        if ((int) $vehicle->vehicleStatus !== 1) {
            return response()->json([
                'approved' => true,
                'message' => 'Own vehicle is ready for LR tracking.',
            ]);
        }

        if ((int) $vehicle->statusStop === 1) {
            return response()->json([
                'approved' => false,
                'message' => 'SIM tracking is stopped for this market vehicle.',
            ]);
        }

        try {
            $details = $this->integrations->findFleetVehicle($vehicle->vehicleNo, $request->user());
        } catch (\Throwable $exception) {
            return response()->json([
                'approved' => false,
                'message' => $exception->getMessage(),
            ], 503);
        }

        return response()->json([
            'approved' => (bool) $details,
            'message' => $details
                ? 'FleetX vehicle approval looks active.'
                : 'Vehicle is not yet available from FleetX live analytics.',
        ]);
    }
}
