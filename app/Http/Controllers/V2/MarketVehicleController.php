<?php

namespace App\Http\Controllers\V2;

use App\Jobs\RegisterMarketVehicleTrackingJob;
use App\Jobs\StopMarketVehicleTrackingJob;
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
            ->select(['id', 'vehicleNo', 'mobileNo', 'simProvider', 'expireDate', 'statusStop', 'created_at'])
            ->where('vehicleStatus', 1)
            ->latest('created_at');

        return $this->datatableResponse(
            $request,
            $query,
            ['vehicleNo', 'mobileNo', 'simProvider'],
            ['created_at', 'vehicleNo', 'mobileNo', 'simProvider', 'expireDate', 'statusStop', null],
            function (Vehicle $vehicle, int $index) {
                $statusBadge = '<span class="badge badge-' . ((int) $vehicle->statusStop === 1 ? 'danger' : 'success') . '">'
                    . ((int) $vehicle->statusStop === 1 ? 'Stopped' : 'Active')
                    . '</span>';
                $canManage = $this->canManageDestructiveActions(request());

                $actions = [
                    $this->actionLink(route('v2.market-vehicles.show', $vehicle), 'View', 'btn-outline-info'),
                    $this->actionLink(route('v2.market-vehicles.edit', $vehicle), 'Edit', 'btn-outline-primary'),
                    $this->actionForm(route('v2.market-vehicles.status', $vehicle), 'Check', 'btn-outline-success'),
                ];

                if ($canManage && (int) $vehicle->statusStop !== 1) {
                    $actions[] = $this->actionForm(
                        route('v2.market-vehicles.stop-tracking', $vehicle),
                        'Stop',
                        'btn-outline-warning',
                        'POST',
                        'Stop SIM tracking for this vehicle?'
                    );
                }

                if ($canManage) {
                    $actions[] = $this->actionForm(
                        route('v2.market-vehicles.destroy', $vehicle),
                        'Delete',
                        'btn-outline-danger',
                        'DELETE',
                        'Remove this market vehicle?'
                    );
                }

                return [
                    'index' => $index,
                    'vehicleNo' => e($vehicle->vehicleNo),
                    'mobileNo' => e($vehicle->mobileNo),
                    'simProvider' => e($vehicle->simProvider),
                    'expireDate' => e($this->displayDate($vehicle->expireDate)),
                    'statusStop' => $statusBadge,
                    'actions' => $this->actionGroup($actions),
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

        try {
            $this->queueSimRegistration($vehicle, $request, 'market-vehicle-create');
            $message = 'Market vehicle added. FleetX SIM registration has been queued.';
            $messageType = 'success';
        } catch (\Throwable $exception) {
            $this->logHandledException($exception, 'Market Vehicle Registration Queue Failed', $request, [
                'vehicle_id' => $vehicle->id,
                'vehicleNo' => $vehicle->vehicleNo,
            ], 'warning');
            $message = 'Market vehicle saved, but FleetX registration could not be queued: ' . $exception->getMessage();
            $messageType = 'warning';
        }

        return redirect()->route('v2.market-vehicles.index')
            ->with('message', $message)
            ->with('message_type', $messageType);
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
        $wasStopped = (int) $vehicle->statusStop === 1;
        $simChanged = $oldMobileNo !== $validated['mobileNo']
            || $oldSimProvider !== $validated['simProvider'];

        try {
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

        try {
            if (
                $simChanged
                && !$wasStopped
                && $oldMobileNo !== ''
                && $oldSimProvider !== ''
            ) {
                $this->queueSimStop($oldMobileNo, $oldSimProvider, $vehicle->id, $oldVehicleNo, $request, 'market-vehicle-old-sim-cleanup');
            }

            $this->queueSimRegistration($vehicle->fresh(), $request, 'market-vehicle-update');
            $message = 'Market vehicle updated. FleetX SIM updates have been queued.';
            $messageType = 'success';
        } catch (\Throwable $exception) {
            $this->logHandledException($exception, 'Market Vehicle Update Queue Failed', $request, [
                'vehicle_id' => $vehicle->id,
                'vehicleNo' => $validated['vehicleNo'],
            ], 'warning');
            $message = 'Market vehicle updated, but FleetX queueing failed: ' . $exception->getMessage();
            $messageType = 'warning';
        }

        return redirect()->route('v2.market-vehicles.index')
            ->with('message', $message)
            ->with('message_type', $messageType);
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
            $vehicle->statusStop = 1;
            $vehicle->save();
            $this->queueSimStop($vehicle->mobileNo, $vehicle->simProvider, $vehicle->id, $vehicle->vehicleNo, $request, 'market-vehicle-stop', true);
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
            ->with('message', 'SIM tracking stop has been queued.')
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

        $shouldStopRemote = (int) $vehicle->statusStop !== 1
            && trim((string) $vehicle->mobileNo) !== ''
            && trim((string) $vehicle->simProvider) !== '';
        $mobileNo = trim((string) $vehicle->mobileNo);
        $simProvider = strtoupper(trim((string) $vehicle->simProvider));
        $vehicleNo = $vehicle->vehicleNo;
        $vehicleId = (int) $vehicle->id;

        try {
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

        $message = 'Market vehicle removed successfully.';
        $messageType = 'success';

        if ($shouldStopRemote) {
            try {
                $this->queueSimStop($mobileNo, $simProvider, $vehicleId, $vehicleNo, $request, 'market-vehicle-delete');
                $message = 'Market vehicle removed. FleetX SIM stop has been queued.';
            } catch (\Throwable $exception) {
                $this->logHandledException($exception, 'Market Vehicle Delete Stop Queue Failed', $request, [
                    'vehicle_id' => $vehicleId,
                    'vehicleNo' => $vehicleNo,
                    'mobileNo' => $mobileNo,
                    'simProvider' => $simProvider,
                ], 'warning');
                $message = 'Market vehicle removed, but FleetX SIM stop could not be queued: ' . $exception->getMessage();
                $messageType = 'warning';
            }
        }

        return redirect()->route('v2.market-vehicles.index')
            ->with('message', $message)
            ->with('message_type', $messageType);
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

    private function queueSimRegistration(Vehicle $vehicle, Request $request, string $reason): void
    {
        RegisterMarketVehicleTrackingJob::dispatch(
            (int) $vehicle->id,
            $this->simTrackingPayload($vehicle),
            optional($request->user())->id,
            $reason
        );
    }

    private function queueSimStop(
        string $mobileNo,
        string $simProvider,
        ?int $vehicleId,
        ?string $vehicleNo,
        Request $request,
        string $reason,
        bool $markStopped = false
    ): void {
        StopMarketVehicleTrackingJob::dispatch(
            $mobileNo,
            $simProvider,
            $vehicleId,
            $vehicleNo,
            optional($request->user())->id,
            $reason,
            $markStopped
        );
    }

    private function simTrackingPayload(Vehicle $vehicle): array
    {
        return [
            'mobileNumber' => $vehicle->mobileNo,
            'vehicleNumber' => $vehicle->vehicleNo,
            'expiryDate' => $this->formatDateTime($vehicle->expireDate),
            'simProvider' => $vehicle->simProvider,
            'pingFrequency' => '3600',
        ];
    }

    private function canManageDestructiveActions(Request $request): bool
    {
        $user = $request->user();

        return $user && method_exists($user, 'isAdmin') && $user->isAdmin();
    }
}
