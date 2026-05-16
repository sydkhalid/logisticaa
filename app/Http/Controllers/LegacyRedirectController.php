<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class LegacyRedirectController extends Controller
{
    public function index(): RedirectResponse
    {
        return Auth::check()
            ? redirect()->route('v2.home')
            : redirect()->route('v2.login');
    }

    public function home(): RedirectResponse
    {
        return redirect()->route('v2.home');
    }

    public function vehicleIndex(): RedirectResponse
    {
        return redirect()->route('v2.vehicles.index');
    }

    public function vehicleCreate(): RedirectResponse
    {
        return redirect()->route('v2.vehicles.create');
    }

    public function vehicleShow($vehicle): RedirectResponse
    {
        return redirect()->route('v2.vehicles.show', $vehicle);
    }

    public function vehicleEdit($vehicle): RedirectResponse
    {
        return redirect()->route('v2.vehicles.edit', $vehicle);
    }

    public function marketVehicleIndex(): RedirectResponse
    {
        return redirect()->route('v2.market-vehicles.index');
    }

    public function marketVehicleCreate(): RedirectResponse
    {
        return redirect()->route('v2.market-vehicles.create');
    }

    public function marketVehicleShow($vehicle): RedirectResponse
    {
        return redirect()->route('v2.market-vehicles.show', $vehicle);
    }

    public function marketVehicleEdit($vehicle): RedirectResponse
    {
        return redirect()->route('v2.market-vehicles.edit', $vehicle);
    }

    public function lrTrackingIndex(): RedirectResponse
    {
        return redirect()->route('v2.lr-trackings.index');
    }

    public function lrTrackingCompleted(): RedirectResponse
    {
        return redirect()->route('v2.lr-trackings.completed');
    }

    public function lrTrackingCreate(): RedirectResponse
    {
        return redirect()->route('v2.lr-trackings.create');
    }

    public function lrTrackingShow($tracking): RedirectResponse
    {
        return redirect()->route('v2.lr-trackings.show', $tracking);
    }

    public function lrTrackingEdit($tracking): RedirectResponse
    {
        return redirect()->route('v2.lr-trackings.edit', $tracking);
    }

    public function epodIndex(): RedirectResponse
    {
        return redirect()->route('v2.epods.index');
    }

    public function epodCreate(): RedirectResponse
    {
        return redirect()->route('v2.epods.create');
    }

    public function weightCorrectionIndex(): RedirectResponse
    {
        return redirect()->route('v2.weight-corrections.index');
    }

    public function weightCorrectionCreate(): RedirectResponse
    {
        return redirect()->route('v2.weight-corrections.create');
    }

    public function weightCorrectionEdit($weight): RedirectResponse
    {
        return redirect()->route('v2.weight-corrections.edit', $weight);
    }

    public function settings(): RedirectResponse
    {
        return redirect()->route('v2.settings.edit');
    }
}
