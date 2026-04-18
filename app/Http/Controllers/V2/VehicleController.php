<?php

namespace App\Http\Controllers\V2;

use App\Models\Vehicle;
use Illuminate\Http\Request;
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
            'vehicles' => Vehicle::query()
                ->where('vehicleStatus', 0)
                ->latest('id')
                ->get(),
        ]);
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
        $validated = $request->validate([
            'vehicleNo' => ['required', 'string', Rule::unique('vehicles', 'vehicleNo')],
        ]);

        $vehicle = new Vehicle();
        $vehicle->vehicleNo = strtoupper(trim($validated['vehicleNo']));
        $vehicle->vehicleStatus = 0;
        $vehicle->statusStop = 0;
        $vehicle->save();

        return redirect()->route('v2.vehicles.index')
            ->with('message', 'Own vehicle added successfully.')
            ->with('message_type', 'success');
    }

    public function show(Vehicle $vehicle)
    {
        $location = null;
        $warning = null;

        try {
            $location = $this->integrations->locateVehicle($vehicle, auth()->user());
        } catch (\Throwable $exception) {
            $warning = $exception->getMessage();
        }

        return $this->render('vehicles.show', [
            'pageTitle' => 'Own Vehicle Details',
            'vehicle' => $vehicle,
            'location' => $location,
            'warning' => $warning,
        ]);
    }

    public function edit(Vehicle $vehicle)
    {
        return $this->render('vehicles.form', [
            'pageTitle' => 'Edit Own Vehicle',
            'vehicle' => $vehicle,
            'formAction' => route('v2.vehicles.update', $vehicle),
            'formMethod' => 'PUT',
        ]);
    }

    public function update(Request $request, Vehicle $vehicle)
    {
        $validated = $request->validate([
            'vehicleNo' => [
                'required',
                'string',
                Rule::unique('vehicles', 'vehicleNo')->ignore($vehicle->id),
            ],
        ]);

        $vehicle->vehicleNo = strtoupper(trim($validated['vehicleNo']));
        $vehicle->save();

        return redirect()->route('v2.vehicles.index')
            ->with('message', 'Own vehicle updated successfully.')
            ->with('message_type', 'success');
    }

    public function destroy(Vehicle $vehicle)
    {
        $vehicle->delete();

        return redirect()->route('v2.vehicles.index')
            ->with('message', 'Own vehicle removed successfully.')
            ->with('message_type', 'success');
    }
}
