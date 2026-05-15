<?php

namespace App\Http\Controllers\V2;

use App\Models\Tracking;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class MarketVehicleController extends BaseController
{
    public function __construct(\App\Services\V2\ExternalLogisticsService $integrations)
    {
        parent::__construct($integrations);
        $this->middleware('auth');
    }

    public function index()
    {
        return $this->render('market-vehicles.index', [
            'pageTitle' => 'Market Vehicles',
        ]);
    }

    public function data(Request $request)
    {
        $query = Vehicle::query()
            ->select(['id', 'vehicleNo', 'mobileNo', 'simProvider', 'expireDate', 'statusStop'])
            ->where('vehicleStatus', 1)
            ->latest('id');

        return $this->datatableResponse(
            $request,
            $query,
            ['vehicleNo', 'mobileNo', 'simProvider'],
            ['id', 'vehicleNo', 'mobileNo', 'simProvider', 'expireDate', 'statusStop', null],
            function (Vehicle $vehicle, int $index) {
                $statusBadge = '<span class="badge badge-' . ((int) $vehicle->statusStop === 1 ? 'danger' : 'success') . '">'
                    . ((int) $vehicle->statusStop === 1 ? 'Stopped' : 'Active')
                    . '</span>';

                return [
                    'index' => $index,
                    'vehicleNo' => e($vehicle->vehicleNo),
                    'mobileNo' => e($vehicle->mobileNo),
                    'simProvider' => e($vehicle->simProvider),
                    'expireDate' => e($this->displayDate($vehicle->expireDate)),
                    'statusStop' => $statusBadge,
                    'actions' => $this->actionGroup([
                        $this->actionLink(route('v2.market-vehicles.show', $vehicle), 'View', 'btn-outline-info'),
                        $this->actionLink(route('v2.market-vehicles.edit', $vehicle), 'Edit', 'btn-outline-primary'),
                        $this->actionForm(route('v2.market-vehicles.status', $vehicle), 'Check', 'btn-outline-success'),
                        $this->actionForm(
                            route('v2.market-vehicles.stop-tracking', $vehicle),
                            'Stop',
                            'btn-outline-warning',
                            'POST',
                            'Stop SIM tracking for this vehicle?'
                        ),
                        $this->actionForm(
                            route('v2.market-vehicles.destroy', $vehicle),
                            'Delete',
                            'btn-outline-danger',
                            'DELETE',
                            'Remove this market vehicle?'
                        ),
                    ]),
                ];
            }
        );
    }

    public function create()
    {
        return $this->render('market-vehicles.form', [
            'pageTitle' => 'Add Market Vehicle',
            'vehicle' => new Vehicle(),
            'formAction' => route('v2.market-vehicles.store'),
            'formMethod' => 'POST',
        ]);
    }

    public function store(Request $request)
    {
        $this->normalizePayload($request);
        $validated = $this->validatePayload($request);
        $expireDate = $this->formatDateTime($validated['expireDate']);

        try {
            $this->integrations->registerSimTracking([
                'mobileNumber' => $validated['mobileNo'],
                'vehicleNumber' => $validated['vehicleNo'],
                'expiryDate' => $expireDate,
                'simProvider' => $validated['simProvider'],
                'pingFrequency' => '3600',
            ], $request->user());

            $vehicle = new Vehicle();
            $vehicle->vehicleNo = $validated['vehicleNo'];
            $vehicle->mobileNo = $validated['mobileNo'];
            $vehicle->expireDate = $expireDate;
            $vehicle->simProvider = $validated['simProvider'];
            $vehicle->vehicleStatus = 1;
            $vehicle->statusStop = 0;
            $vehicle->save();
        } catch (\Throwable $exception) {
            $this->logHandledException($exception, 'Market Vehicle Registration Failed', $request, [
                'vehicleNo' => $validated['vehicleNo'],
                'mobileNo' => $validated['mobileNo'],
            ]);
            return back()
                ->withInput()
                ->with('message', $exception->getMessage())
                ->with('message_type', 'danger');
        }

        return redirect()->route('v2.market-vehicles.index')
            ->with('message', 'Market vehicle added successfully.')
            ->with('message_type', 'success');
    }

    public function show($vehicle)
    {
        $vehicle = $this->resolveVehicle($vehicle);
        $details = null;
        $warning = null;

        try {
            $details = $this->integrations->findFleetVehicle($vehicle->vehicleNo, auth()->user());
        } catch (\Throwable $exception) {
            $this->logHandledException($exception, 'Market Vehicle Lookup Failed', request(), [
                'vehicle_id' => $vehicle->id,
                'vehicleNo' => $vehicle->vehicleNo,
            ], 'warning');
            $warning = $exception->getMessage();
        }

        return $this->render('market-vehicles.show', [
            'pageTitle' => 'Market Vehicle Details',
            'vehicle' => $vehicle,
            'details' => $details,
            'warning' => $warning,
        ]);
    }

    public function edit($vehicle)
    {
        $vehicle = $this->resolveVehicle($vehicle);

        return $this->render('market-vehicles.form', [
            'pageTitle' => 'Update Market Vehicle',
            'vehicle' => $vehicle,
            'formAction' => route('v2.market-vehicles.update', $vehicle),
            'formMethod' => 'PUT',
        ]);
    }

    public function update(Request $request, $vehicle)
    {
        $vehicle = $this->resolveVehicle($vehicle);
        $this->normalizePayload($request);
        $validated = $this->validatePayload($request, $vehicle);
        $expireDate = $this->formatDateTime($validated['expireDate']);
        $oldVehicleNo = $vehicle->vehicleNo;
        $oldMobileNo = trim((string) $vehicle->mobileNo);
        $oldSimProvider = strtoupper(trim((string) $vehicle->simProvider));

        $simChanged = $oldMobileNo !== $validated['mobileNo']
            || $oldSimProvider !== $validated['simProvider'];

        try {
            if (
                $simChanged
                && (int) $vehicle->statusStop !== 1
                && $oldMobileNo !== ''
                && $oldSimProvider !== ''
            ) {
                try {
                    $this->integrations->stopSimTracking($oldMobileNo, $oldSimProvider, $request->user());
                } catch (\Throwable $exception) {
                    $this->logHandledException($exception, 'Market Vehicle Old SIM Cleanup Failed', $request, [
                        'vehicle_id' => $vehicle->id,
                        'vehicleNo' => $oldVehicleNo,
                        'mobileNo' => $oldMobileNo,
                        'simProvider' => $oldSimProvider,
                    ], 'warning');
                }
            }

            $this->integrations->registerSimTracking([
                'mobileNumber' => $validated['mobileNo'],
                'vehicleNumber' => $validated['vehicleNo'],
                'expiryDate' => $expireDate,
                'simProvider' => $validated['simProvider'],
                'pingFrequency' => '3600',
            ], $request->user());

            DB::transaction(function () use ($vehicle, $validated, $expireDate, $oldVehicleNo) {
                $vehicle->vehicleNo = $validated['vehicleNo'];
                $vehicle->mobileNo = $validated['mobileNo'];
                $vehicle->expireDate = $expireDate;
                $vehicle->simProvider = $validated['simProvider'];
                $vehicle->statusStop = 0;
                $vehicle->save();

                if ($oldVehicleNo !== $validated['vehicleNo']) {
                    Tracking::query()
                        ->where('vehicleNo', $oldVehicleNo)
                        ->update(['vehicleNo' => $validated['vehicleNo']]);
                }
            });
        } catch (\Throwable $exception) {
            $this->logHandledException($exception, 'Market Vehicle Update Failed', $request, [
                'vehicle_id' => $vehicle->id,
                'vehicleNo' => $validated['vehicleNo'],
            ]);
            return back()
                ->withInput()
                ->with('message', $exception->getMessage())
                ->with('message_type', 'danger');
        }

        return redirect()->route('v2.market-vehicles.index')
            ->with('message', 'Market vehicle updated successfully.')
            ->with('message_type', 'success');
    }

    public function status(Request $request, $vehicle)
    {
        $vehicle = $this->resolveVehicle($vehicle);

        try {
            $details = $this->integrations->findFleetVehicle($vehicle->vehicleNo, $request->user());
        } catch (\Throwable $exception) {
            $this->logHandledException($exception, 'Market Vehicle Status Check Failed', $request, [
                'vehicle_id' => $vehicle->id,
                'vehicleNo' => $vehicle->vehicleNo,
            ], 'warning');
            return back()
                ->with('message', $exception->getMessage())
                ->with('message_type', 'danger');
        }

        return back()
            ->with('message', $details
                ? 'Current FleetX status: ' . ($details['currentStatus'] ?? 'Unknown')
                : 'Vehicle is not yet available on FleetX.')
            ->with('message_type', $details ? 'success' : 'warning');
    }

    public function stopTracking(Request $request, $vehicle)
    {
        $vehicle = $this->resolveVehicle($vehicle);

        if ((int) $vehicle->statusStop === 1) {
            return back()
                ->with('message', 'SIM tracking is already stopped for this vehicle.')
                ->with('message_type', 'warning');
        }

        if ($this->hasActiveTrackings($vehicle->vehicleNo)) {
            return back()
                ->with('message', 'This market vehicle has an active LR tracking record. Complete the LR before stopping SIM tracking.')
                ->with('message_type', 'warning');
        }

        try {
            $this->integrations->stopSimTracking($vehicle->mobileNo, $vehicle->simProvider, $request->user());
            $vehicle->statusStop = 1;
            $vehicle->save();
        } catch (\Throwable $exception) {
            $this->logHandledException($exception, 'Market Vehicle Stop Tracking Failed', $request, [
                'vehicle_id' => $vehicle->id,
                'vehicleNo' => $vehicle->vehicleNo,
                'mobileNo' => $vehicle->mobileNo,
            ]);
            return back()
                ->with('message', $exception->getMessage())
                ->with('message_type', 'danger');
        }

        return back()
            ->with('message', 'SIM tracking stopped successfully.')
            ->with('message_type', 'success');
    }

    public function destroy(Request $request, $vehicle)
    {
        $vehicle = $this->resolveVehicle($vehicle);

        if ($this->hasActiveTrackings($vehicle->vehicleNo)) {
            return redirect()->route('v2.market-vehicles.index')
                ->with('message', 'This market vehicle has an active LR tracking record and cannot be removed yet.')
                ->with('message_type', 'warning');
        }

        try {
            if (
                (int) $vehicle->statusStop !== 1
                && trim((string) $vehicle->mobileNo) !== ''
                && trim((string) $vehicle->simProvider) !== ''
            ) {
                $this->integrations->stopSimTracking($vehicle->mobileNo, $vehicle->simProvider, $request->user());
            }

            $vehicle->delete();
        } catch (\Throwable $exception) {
            $this->logHandledException($exception, 'Market Vehicle Delete Failed', $request, [
                'vehicle_id' => $vehicle->id,
                'vehicleNo' => $vehicle->vehicleNo,
            ]);

            return redirect()->route('v2.market-vehicles.index')
                ->with('message', $exception->getMessage())
                ->with('message_type', 'danger');
        }

        return redirect()->route('v2.market-vehicles.index')
            ->with('message', 'Market vehicle removed successfully.')
            ->with('message_type', 'success');
    }

    private function validatePayload(Request $request, ?Vehicle $vehicle = null): array
    {
        return $request->validate([
            'vehicleNo' => [
                'required',
                'string',
                Rule::unique('vehicles', 'vehicleNo')->ignore($vehicle ? $vehicle->id : null),
            ],
            'mobileNo' => ['required', 'string'],
            'expireDate' => ['required', 'date'],
            'simProvider' => ['required', 'string'],
        ]);
    }

    private function normalizePayload(Request $request): void
    {
        $request->merge([
            'vehicleNo' => strtoupper(trim((string) $request->input('vehicleNo'))),
            'mobileNo' => trim((string) $request->input('mobileNo')),
            'simProvider' => strtoupper(trim((string) $request->input('simProvider'))),
        ]);
    }

    private function resolveVehicle($vehicle)
    {
        return Vehicle::query()
            ->where('vehicleStatus', 1)
            ->where('id', $vehicle)
            ->firstOrFail();
    }

    private function hasActiveTrackings(string $vehicleNo)
    {
        return Tracking::query()
            ->where('vehicleNo', $vehicleNo)
            ->where('status', 0)
            ->exists();
    }
}
