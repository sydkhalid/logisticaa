<?php

use App\Http\Controllers\V2\AuthController;
use App\Http\Controllers\V2\DashboardController;
use App\Http\Controllers\V2\EpodController;
use App\Http\Controllers\V2\LrTrackingController;
use App\Http\Controllers\V2\MarketVehicleController;
use App\Http\Controllers\V2\ReportController;
use App\Http\Controllers\V2\SettingController;
use App\Http\Controllers\V2\VehicleController;
use App\Http\Controllers\V2\WeightCorrectionController;

/** @var \Illuminate\Routing\Router $router */

$router->prefix('v2')->name('v2.')->group(function () use ($router) {
    $router->get('/', function () {
        return auth()->check()
            ? redirect()->route('v2.home')
            : redirect()->route('v2.login');
    })->name('index');

    $router->middleware('guest')->group(function () use ($router) {
        $router->get('/login', [AuthController::class, 'showLogin'])->name('login');
        $router->post('/login', [AuthController::class, 'login'])->name('login.submit');
    });

    $router->middleware('auth')->group(function () use ($router) {
        $router->post('/logout', [AuthController::class, 'logout'])->name('logout');

        $router->get('/home', [DashboardController::class, 'index'])->name('home');

        $router->prefix('vehicles')->name('vehicles.')->group(function () use ($router) {
            $router->get('/', [VehicleController::class, 'index'])->name('index');
            $router->get('/create', [VehicleController::class, 'create'])->name('create');
            $router->post('/', [VehicleController::class, 'store'])->name('store');
            $router->get('/{vehicle}', [VehicleController::class, 'show'])->name('show');
            $router->get('/{vehicle}/edit', [VehicleController::class, 'edit'])->name('edit');
            $router->put('/{vehicle}', [VehicleController::class, 'update'])->name('update');
            $router->delete('/{vehicle}', [VehicleController::class, 'destroy'])->name('destroy');
        });

        $router->prefix('market-vehicles')->name('market-vehicles.')->group(function () use ($router) {
            $router->get('/', [MarketVehicleController::class, 'index'])->name('index');
            $router->get('/create', [MarketVehicleController::class, 'create'])->name('create');
            $router->post('/', [MarketVehicleController::class, 'store'])->name('store');
            $router->get('/{vehicle}', [MarketVehicleController::class, 'show'])->name('show');
            $router->get('/{vehicle}/edit', [MarketVehicleController::class, 'edit'])->name('edit');
            $router->put('/{vehicle}', [MarketVehicleController::class, 'update'])->name('update');
            $router->delete('/{vehicle}', [MarketVehicleController::class, 'destroy'])->name('destroy');
            $router->post('/{vehicle}/status', [MarketVehicleController::class, 'status'])->name('status');
            $router->post('/{vehicle}/stop-tracking', [MarketVehicleController::class, 'stopTracking'])->name('stop-tracking');
        });

        $router->prefix('lr-trackings')->name('lr-trackings.')->group(function () use ($router) {
            $router->get('/', [LrTrackingController::class, 'index'])->name('index');
            $router->get('/completed', [LrTrackingController::class, 'completed'])->name('completed');
            $router->get('/create', [LrTrackingController::class, 'create'])->name('create');
            $router->post('/', [LrTrackingController::class, 'store'])->name('store');
            $router->post('/vehicle-availability', [LrTrackingController::class, 'checkVehicleAvailability'])->name('vehicle-availability');
            $router->post('/{tracking}/refresh', [LrTrackingController::class, 'refresh'])->name('refresh');
            $router->get('/{tracking}', [LrTrackingController::class, 'show'])->name('show');
            $router->get('/{tracking}/edit', [LrTrackingController::class, 'edit'])->name('edit');
            $router->put('/{tracking}', [LrTrackingController::class, 'update'])->name('update');
        });

        $router->prefix('weight-corrections')->name('weight-corrections.')->group(function () use ($router) {
            $router->get('/', [WeightCorrectionController::class, 'index'])->name('index');
            $router->get('/create', [WeightCorrectionController::class, 'create'])->name('create');
            $router->post('/', [WeightCorrectionController::class, 'store'])->name('store');
            $router->post('/fetch-lr', [WeightCorrectionController::class, 'fetchLr'])->name('fetch-lr');
            $router->get('/{weight}/edit', [WeightCorrectionController::class, 'edit'])->name('edit');
            $router->put('/{weight}', [WeightCorrectionController::class, 'update'])->name('update');
        });

        $router->prefix('epods')->name('epods.')->group(function () use ($router) {
            $router->get('/', [EpodController::class, 'index'])->name('index');
            $router->get('/create', [EpodController::class, 'create'])->name('create');
            $router->post('/', [EpodController::class, 'store'])->name('store');
        });

        $router->prefix('reports')->name('reports.')->group(function () use ($router) {
            $router->get('/', [ReportController::class, 'index'])->name('index');
            $router->get('/export/{dataset}', [ReportController::class, 'export'])->name('export');
        });

        $router->get('/settings', [SettingController::class, 'edit'])->name('settings.edit');
        $router->post('/settings', [SettingController::class, 'update'])->name('settings.update');
    });
});
