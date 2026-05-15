<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\VehicleController;
use App\Http\Controllers\LrtrackingController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\TrackController;
use App\Http\Controllers\VehicleotherController;
use App\Http\Controllers\WeightController;
use App\Http\Controllers\V2\AuthController as V2AuthController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('v2.home')
        : redirect()->route('v2.login');
})->name('index');
Route::get('/login', function () {
    return auth()->check()
        ? redirect()->route('v2.home')
        : redirect()->route('v2.login');
})->name('login');
Route::post('/login', [V2AuthController::class, 'login'])
    ->middleware('throttle:5,1')
    ->name('login.submit');

Route::group(['middleware' => ['auth']], function () {
    //home
    Route::get('/home', function () {
        return redirect()->route('v2.home');
    })->name('home');
    Route::get('/dashboard', function () {
        return redirect()->route('v2.home');
    });
    Route::get('vehicle/create', function () {
        return redirect()->route('v2.vehicles.create');
    });
    Route::get('vehicle/{vehicle}/edit', function ($vehicle) {
        return redirect()->route('v2.vehicles.edit', $vehicle);
    });
    Route::get('vehicle/{vehicle}', function ($vehicle) {
        return redirect()->route('v2.vehicles.show', $vehicle);
    });
    Route::get('vehicle', function () {
        return redirect()->route('v2.vehicles.index');
    });
    Route::get('vehicleOther/create', function () {
        return redirect()->route('v2.market-vehicles.create');
    });
    Route::get('vehicleOther/{vehicle}/edit', function ($vehicle) {
        return redirect()->route('v2.market-vehicles.edit', $vehicle);
    });
    Route::get('vehicleOther/{vehicle}', function ($vehicle) {
        return redirect()->route('v2.market-vehicles.show', $vehicle);
    });
    Route::get('vehicleOther', function () {
        return redirect()->route('v2.market-vehicles.index');
    });
    Route::get('lrtracking/create', function () {
        return redirect()->route('v2.lr-trackings.create');
    });
    Route::get('lrtracking/{tracking}/edit', function ($tracking) {
        return redirect()->route('v2.lr-trackings.edit', $tracking);
    });
    Route::get('lrtracking/{tracking}', function ($tracking) {
        return redirect()->route('v2.lr-trackings.show', $tracking);
    });
    Route::get('lrtracking', function () {
        return redirect()->route('v2.lr-trackings.index');
    });
    Route::get('delivered_list', function () {
        return redirect()->route('v2.lr-trackings.completed');
    })->name('delivered_list');
    Route::get('/epod', function () {
        return redirect()->route('v2.epods.index');
    })->name('epod');
    Route::get('epod/add_epod', function () {
        return redirect()->route('v2.epods.create');
    })->name('add_epod');
    Route::get('weight-correction/create', function () {
        return redirect()->route('v2.weight-corrections.create');
    });
    Route::get('weight-correction/{weight}/edit', function ($weight) {
        return redirect()->route('v2.weight-corrections.edit', $weight);
    });
    Route::get('weight-correction', function () {
        return redirect()->route('v2.weight-corrections.index');
    });
    Route::get('settings', function () {
        return redirect()->route('v2.settings.edit');
    });
    // vehicle
    Route::resource('vehicle', VehicleController::class);
    Route::resource('vehicleOther', VehicleotherController::class);
    Route::post('fetch_vehicles', [VehicleController::class, 'fetch_vehicles'])->name('fetch_vehicles');
    Route::get('vehicleStopTrack/{id}', [VehicleotherController::class, 'vehicleStopTrack'])->name('vehicleStopTrack');
    Route::get('vehiclecheckTrack/{id}', [VehicleotherController::class, 'vehiclecheckTrack'])->name('vehiclecheckTrack');

    //tracking LR
    Route::resource('lrtracking', LrtrackingController::class);
    Route::get('lrtracking_again/{vehicle_no}/{lrnumber}', [TrackController::class, 'getsinglelrtracking'])->name('getsinglelrtracking');
    Route::post('epod/epod_upload', [HomeController::class, 'epod_upload'])->name('epod_upload');
    //weight correction
    Route::resource('weight-correction', WeightController::class);
    Route::post('fetchlr', [WeightController::class, 'fetchlr'])->name('fetchlr');
    //logout
    Route::post('logout', [AuthController::class, 'logout'])->name('logout');
    //settings
    Route::resource('settings', SettingController::class);
    Route::post('settings/save', [SettingController::class, 'store']);
});

require base_path('routes/web_v2.php');
