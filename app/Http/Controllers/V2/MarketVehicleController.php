<?php

namespace App\Http\Controllers\V2;

use App\Models\Vehicle;
use Illuminate\Http\Request;
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
            'vehicles' => Vehicle::query()
                ->where('vehicleStatus', 1)
                ->latest('id')
                ->get(),
        ]);
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
        $validated = $this->validatePayload($request);
        $expireDate = $this->formatDateTime($validated['expireDate']);

        try {
            $this->integrations->registerSimTracking([
                'mobileNumber' => $validated['mobileNo'],
                'vehicleNumber' => strtoupper(trim($validated['vehicleNo'])),
                'expiryDate' => $expireDate,
                'simProvider' => $validated['simProvider'],
                'pingFrequency' => '3600',
            ], $request->user());
        } catch (\Throwable $exception) {
            return back()
                ->withInput()
                ->with('message', $exception->getMessage())
                ->with('message_type', 'danger');
        }

        $vehicle = new Vehicle();
        $vehicle->vehicleNo = strtoupper(trim($validated['vehicleNo']));
        $vehicle->mobileNo = $validated['mobileNo'];
        $vehicle->expireDate = $expireDate;
        $vehicle->simProvider = $validated['simProvider'];
        $vehicle->vehicleStatus = 1;
        $vehicle->statusStop = 0;
        $vehicle->save();

        return redirect()->route('v2.market-vehicles.index')
            ->with('message', 'Market vehicle added successfully.')
            ->with('message_type', 'success');
    }

    public function show(Vehicle $vehicle)
    {
        $details = null;
        $warning = null;

        try {
            $details = $this->integrations->findFleetVehicle($vehicle->vehicleNo, auth()->user());
        } catch (\Throwable $exception) {
            $warning = $exception->getMessage();
        }

        return $this->render('market-vehicles.show', [
            'pageTitle' => 'Market Vehicle Details',
            'vehicle' => $vehicle,
            'details' => $details,
            'warning' => $warning,
        ]);
    }

    public function edit(Vehicle $vehicle)
    {
        return $this->render('market-vehicles.form', [
            'pageTitle' => 'Update Market Vehicle',
            'vehicle' => $vehicle,
            'formAction' => route('v2.market-vehicles.update', $vehicle),
            'formMethod' => 'PUT',
        ]);
    }

    public function update(Request $request, Vehicle $vehicle)
    {
        $validated = $this->validatePayload($request, $vehicle);
        $expireDate = $this->formatDateTime($validated['expireDate']);

        try {
            $this->integrations->registerSimTracking([
                'mobileNumber' => $validated['mobileNo'],
                'vehicleNumber' => strtoupper(trim($validated['vehicleNo'])),
                'expiryDate' => $expireDate,
                'simProvider' => $validated['simProvider'],
                'pingFrequency' => '3600',
            ], $request->user());
        } catch (\Throwable $exception) {
            return back()
                ->withInput()
                ->with('message', $exception->getMessage())
                ->with('message_type', 'danger');
        }

        $vehicle->vehicleNo = strtoupper(trim($validated['vehicleNo']));
        $vehicle->mobileNo = $validated['mobileNo'];
        $vehicle->expireDate = $expireDate;
        $vehicle->simProvider = $validated['simProvider'];
        $vehicle->save();

        return redirect()->route('v2.market-vehicles.index')
            ->with('message', 'Market vehicle updated successfully.')
            ->with('message_type', 'success');
    }

    public function status(Request $request, Vehicle $vehicle)
    {
        try {
            $details = $this->integrations->findFleetVehicle($vehicle->vehicleNo, $request->user());
        } catch (\Throwable $exception) {
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

    public function stopTracking(Request $request, Vehicle $vehicle)
    {
        if ((int) $vehicle->statusStop === 1) {
            return back()
                ->with('message', 'SIM tracking is already stopped for this vehicle.')
                ->with('message_type', 'warning');
        }

        try {
            $this->integrations->stopSimTracking($vehicle->mobileNo, $vehicle->simProvider, $request->user());
        } catch (\Throwable $exception) {
            return back()
                ->with('message', $exception->getMessage())
                ->with('message_type', 'danger');
        }

        $vehicle->statusStop = 1;
        $vehicle->save();

        return back()
            ->with('message', 'SIM tracking stopped successfully.')
            ->with('message_type', 'success');
    }

    public function destroy(Vehicle $vehicle)
    {
        $vehicle->delete();

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
}
