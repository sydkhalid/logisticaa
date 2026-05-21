<?php

namespace App\Http\Controllers\V2;

use App\Models\Tracking;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class VehicleController extends BaseController
{
    public function __construct(\App\Services\V2\ExternalLogisticsService $integrations)
    {
        parent::__construct($integrations);
        $this->middleware('auth');
    }

    public function index()
    {
        return $this->render('vehicles.index', [
            'pageTitle' => 'Own Vehicles',
        ]);
    }

    public function data(Request $request)
    {
        $query = Vehicle::query()
            ->select(['id', 'vehicleNo', 'created_at'])
            ->where('vehicleStatus', 0)
            ->latest('created_at');

        return $this->datatableResponse(
            $request,
            $query,
            ['vehicleNo'],
            ['created_at', 'vehicleNo', 'created_at', null],
            function (Vehicle $vehicle, int $index) {
                $actions = [
                    $this->actionLink(route('v2.vehicles.show', $vehicle), 'View', 'btn-outline-info'),
                    $this->actionLink(route('v2.vehicles.edit', $vehicle), 'Edit', 'btn-outline-primary'),
                ];

                if ($this->canManageDestructiveActions(request())) {
                    $actions[] = $this->actionForm(
                        route('v2.vehicles.destroy', $vehicle),
                        'Delete',
                        'btn-outline-danger',
                        'DELETE',
                        'Remove this vehicle?'
                    );
                }

                return [
                    'index' => $index,
                    'vehicleNo' => e($vehicle->vehicleNo),
                    'created_at' => e($this->displayDate($vehicle->created_at)),
                    'actions' => $this->actionGroup($actions),
                ];
            }
        );
    }

    public function create()
    {
        return $this->render('vehicles.form', [
            'pageTitle' => 'Add Own Vehicle',
            'vehicle' => new Vehicle(),
            'formAction' => route('v2.vehicles.store'),
            'formMethod' => 'POST',
        ]);
    }

    public function store(Request $request)
    {
        $request->merge([
            'vehicleNo' => strtoupper(trim((string) $request->input('vehicleNo'))),
        ]);

        $validated = $request->validate([
            'vehicleNo' => ['required', 'string', Rule::unique('vehicles', 'vehicleNo')],
        ]);

        try {
            $vehicle = new Vehicle();
            $vehicle->vehicleNo = strtoupper(trim($validated['vehicleNo']));
            $vehicle->vehicleStatus = 0;
            $vehicle->statusStop = 0;
            $vehicle->save();
        } catch (\Throwable $exception) {
            $this->logHandledException($exception, 'Own Vehicle Create Failed', $request, [
                'vehicleNo' => $validated['vehicleNo'],
            ]);

            return back()
                ->withInput()
                ->with('message', $exception->getMessage())
                ->with('message_type', 'danger');
        }

        return redirect()->route('v2.vehicles.index')
            ->with('message', 'Own vehicle added successfully.')
            ->with('message_type', 'success');
    }

    public function show($vehicle)
    {
        $vehicle = $this->resolveVehicle($vehicle);
        $location = null;
        $warning = null;

        try {
            $location = $this->integrations->locateVehicle($vehicle, auth()->user());
        } catch (\Throwable $exception) {
            $this->logHandledException($exception, 'Own Vehicle Location Lookup Failed', request(), [
                'vehicle_id' => $vehicle->id,
                'vehicleNo' => $vehicle->vehicleNo,
            ], 'warning');
            $warning = $exception->getMessage();
        }

        return $this->render('vehicles.show', [
            'pageTitle' => 'Own Vehicle Details',
            'vehicle' => $vehicle,
            'location' => $location,
            'warning' => $warning,
        ]);
    }

    public function edit($vehicle)
    {
        $vehicle = $this->resolveVehicle($vehicle);

        return $this->render('vehicles.form', [
            'pageTitle' => 'Edit Own Vehicle',
            'vehicle' => $vehicle,
            'formAction' => route('v2.vehicles.update', $vehicle),
            'formMethod' => 'PUT',
        ]);
    }

    public function update(Request $request, $vehicle)
    {
        $vehicle = $this->resolveVehicle($vehicle);
        $request->merge([
            'vehicleNo' => strtoupper(trim((string) $request->input('vehicleNo'))),
        ]);

        $validated = $request->validate([
            'vehicleNo' => [
                'required',
                'string',
                Rule::unique('vehicles', 'vehicleNo')->ignore($vehicle->id),
            ],
        ]);

        try {
            $oldVehicleNo = $vehicle->vehicleNo;
            $newVehicleNo = $validated['vehicleNo'];

            DB::transaction(function () use ($vehicle, $oldVehicleNo, $newVehicleNo) {
                $vehicle->vehicleNo = $newVehicleNo;
                $vehicle->save();

                if ($oldVehicleNo !== $newVehicleNo) {
                    Tracking::query()
                        ->where('vehicleNo', $oldVehicleNo)
                        ->update(['vehicleNo' => $newVehicleNo]);
                }
            });
        } catch (\Throwable $exception) {
            $this->logHandledException($exception, 'Own Vehicle Update Failed', $request, [
                'vehicle_id' => $vehicle->id,
                'vehicleNo' => $validated['vehicleNo'],
            ]);

            return back()
                ->withInput()
                ->with('message', $exception->getMessage())
                ->with('message_type', 'danger');
        }

        return redirect()->route('v2.vehicles.index')
            ->with('message', 'Own vehicle updated successfully.')
            ->with('message_type', 'success');
    }

    public function destroy(Request $request, $vehicle)
    {
        $vehicle = $this->resolveVehicle($vehicle);

        if ($this->hasActiveTrackings($vehicle->vehicleNo)) {
            return redirect()->route('v2.vehicles.index')
                ->with('message', 'This own vehicle has an active LR tracking record and cannot be removed yet.')
                ->with('message_type', 'warning');
        }

        try {
            $vehicle->delete();
        } catch (\Throwable $exception) {
            $this->logHandledException($exception, 'Own Vehicle Delete Failed', $request, [
                'vehicle_id' => $vehicle->id,
                'vehicleNo' => $vehicle->vehicleNo,
            ]);

            return redirect()->route('v2.vehicles.index')
                ->with('message', $exception->getMessage())
                ->with('message_type', 'danger');
        }

        return redirect()->route('v2.vehicles.index')
            ->with('message', 'Own vehicle removed successfully.')
            ->with('message_type', 'success');
    }

    private function resolveVehicle($vehicle)
    {
        return Vehicle::query()
            ->where('vehicleStatus', 0)
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

    private function canManageDestructiveActions(Request $request): bool
    {
        $user = $request->user();

        return $user && method_exists($user, 'isAdmin') && $user->isAdmin();
    }
}
