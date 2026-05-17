<?php

use App\Http\Controllers\V2\AuthController;
use App\Http\Controllers\V2\DashboardController;
use App\Http\Controllers\V2\EpodController;
use App\Http\Controllers\V2\IntegrationController;
use App\Http\Controllers\V2\LrTrackingController;
use App\Http\Controllers\V2\MaintenanceController;
use App\Http\Controllers\V2\MarketVehicleController;
use App\Http\Controllers\V2\RedirectController;
use App\Http\Controllers\V2\ReportController;
use App\Http\Controllers\V2\SettingController;
use App\Http\Controllers\V2\SystemLogController;
use App\Http\Controllers\V2\VehicleController;
use App\Http\Controllers\V2\WeightCorrectionController;
use App\Support\V2Routing;

/** @var \Illuminate\Routing\Router $router */

$router->prefix(V2Routing::routePrefix())->name('v2.')->group(function () use ($router) {
    $router->get('/', [RedirectController::class, 'index'])->name('index');

    $router->middleware('guest')->group(function () use ($router) {
        $router->get('/login', [AuthController::class, 'showLogin'])->name('login');
        $router->post('/login', [AuthController::class, 'login'])
            ->middleware('throttle:5,1')
            ->name('login.submit');
    });

    $router->middleware('auth')->group(function () use ($router) {
        $router->post('/logout', [AuthController::class, 'logout'])->name('logout');

        $router->get('/home', [DashboardController::class, 'index'])->name('home');
        $router->get('/home/attention/{panel}', [DashboardController::class, 'attention'])->name('home.attention');

        $router->middleware('admin')->group(function () use ($router) {
            $router->get('/integrations', [IntegrationController::class, 'index'])->name('integrations.index');
            $router->post('/integrations/fleetx/refresh-token', [IntegrationController::class, 'refreshFleetToken'])->name('integrations.fleetx.refresh-token');
            $router->post('/integrations/travis/refresh-token', [IntegrationController::class, 'refreshTravisToken'])->name('integrations.travis.refresh-token');
            $router->get('/settings', [SettingController::class, 'edit'])->name('settings.edit');
            $router->post('/settings', [SettingController::class, 'update'])->name('settings.update');
            $router->get('/optimize-clear', [MaintenanceController::class, 'optimizeClear'])->name('optimize-clear');
        });

        $router->prefix('logs')->name('logs.')->middleware('admin')->group(function () use ($router) {
            $router->get('/', [SystemLogController::class, 'index'])->name('index');
            $router->get('/data', [SystemLogController::class, 'data'])->name('data');
            $router->get('/export', [SystemLogController::class, 'export'])->name('export');
            $router->post('/clear-old', [SystemLogController::class, 'clearOld'])->name('clear-old');
            $router->post('/clear', [SystemLogController::class, 'clear'])->name('clear');
            $router->get('/{log}', [SystemLogController::class, 'show'])->name('show');
        });

        $router->prefix('vehicles')->name('vehicles.')->group(function () use ($router) {
            $router->get('/', [VehicleController::class, 'index'])->name('index');
            $router->get('/data', [VehicleController::class, 'data'])->name('data');
            $router->get('/create', [VehicleController::class, 'create'])->name('create');
            $router->post('/', [VehicleController::class, 'store'])->name('store');
            $router->get('/{vehicle}', [VehicleController::class, 'show'])->name('show');
            $router->get('/{vehicle}/edit', [VehicleController::class, 'edit'])->name('edit');
            $router->put('/{vehicle}', [VehicleController::class, 'update'])->name('update');
            $router->delete('/{vehicle}', [VehicleController::class, 'destroy'])->middleware('admin')->name('destroy');
        });

        $router->prefix('market-vehicles')->name('market-vehicles.')->group(function () use ($router) {
            $router->get('/', [MarketVehicleController::class, 'index'])->name('index');
            $router->get('/data', [MarketVehicleController::class, 'data'])->name('data');
            $router->get('/create', [MarketVehicleController::class, 'create'])->name('create');
            $router->post('/', [MarketVehicleController::class, 'store'])->name('store');
            $router->get('/{vehicle}', [MarketVehicleController::class, 'show'])->name('show');
            $router->get('/{vehicle}/edit', [MarketVehicleController::class, 'edit'])->name('edit');
            $router->put('/{vehicle}', [MarketVehicleController::class, 'update'])->name('update');
            $router->delete('/{vehicle}', [MarketVehicleController::class, 'destroy'])->middleware('admin')->name('destroy');
            $router->post('/{vehicle}/status', [MarketVehicleController::class, 'status'])->name('status');
            $router->post('/{vehicle}/stop-tracking', [MarketVehicleController::class, 'stopTracking'])->middleware('admin')->name('stop-tracking');
        });

        $router->prefix('lr-trackings')->name('lr-trackings.')->group(function () use ($router) {
            $router->get('/', [LrTrackingController::class, 'index'])->name('index');
            $router->get('/completed', [LrTrackingController::class, 'completed'])->name('completed');
            $router->get('/data', [LrTrackingController::class, 'activeData'])->name('data');
            $router->get('/completed/data', [LrTrackingController::class, 'completedData'])->name('completed.data');
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
            $router->get('/data', [WeightCorrectionController::class, 'data'])->name('data');
            $router->get('/create', [WeightCorrectionController::class, 'create'])->name('create');
            $router->post('/', [WeightCorrectionController::class, 'store'])->name('store');
            $router->post('/fetch-lr', [WeightCorrectionController::class, 'fetchLr'])->name('fetch-lr');
            $router->get('/{weight}/edit', [WeightCorrectionController::class, 'edit'])->name('edit');
            $router->put('/{weight}', [WeightCorrectionController::class, 'update'])->name('update');
        });

        $router->prefix('epods')->name('epods.')->group(function () use ($router) {
            $router->get('/', [EpodController::class, 'index'])->name('index');
            $router->get('/data', [EpodController::class, 'data'])->name('data');
            $router->get('/create', [EpodController::class, 'create'])->name('create');
            $router->post('/', [EpodController::class, 'store'])->name('store');
            $router->get('/{epod}/preview', [EpodController::class, 'preview'])->name('preview');
            $router->get('/{epod}', [EpodController::class, 'show'])->name('show');
            $router->get('/{epod}/download', [EpodController::class, 'download'])->name('download');
            $router->post('/{epod}/retry', [EpodController::class, 'retry'])->name('retry');
            $router->delete('/{epod}', [EpodController::class, 'destroy'])->middleware('admin')->name('destroy');
        });

        $router->prefix('reports')->name('reports.')->group(function () use ($router) {
            $router->get('/', [ReportController::class, 'index'])->name('index');
            $router->get('/print', [ReportController::class, 'print'])->name('print');
            $router->get('/export/{dataset}', [ReportController::class, 'export'])->name('export');
        });
    });
});
