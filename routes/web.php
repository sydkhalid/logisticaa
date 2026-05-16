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
use App\Http\Controllers\LegacyRedirectController;
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

Route::get('/', [LegacyRedirectController::class, 'index'])->name('index');
Route::get('/login', [LegacyRedirectController::class, 'index'])->name('login');
Route::post('/login', [V2AuthController::class, 'login'])
    ->middleware('throttle:5,1')
    ->name('login.submit');

Route::group(['middleware' => ['auth']], function () {
    //home
    Route::get('/home', [LegacyRedirectController::class, 'home'])->name('home');
    Route::get('/dashboard', [LegacyRedirectController::class, 'home']);
    Route::get('vehicle/create', [LegacyRedirectController::class, 'vehicleCreate']);
    Route::get('vehicle/{vehicle}/edit', [LegacyRedirectController::class, 'vehicleEdit']);
    Route::get('vehicle/{vehicle}', [LegacyRedirectController::class, 'vehicleShow']);
    Route::get('vehicle', [LegacyRedirectController::class, 'vehicleIndex']);
    Route::get('vehicleOther/create', [LegacyRedirectController::class, 'marketVehicleCreate']);
    Route::get('vehicleOther/{vehicle}/edit', [LegacyRedirectController::class, 'marketVehicleEdit']);
    Route::get('vehicleOther/{vehicle}', [LegacyRedirectController::class, 'marketVehicleShow']);
    Route::get('vehicleOther', [LegacyRedirectController::class, 'marketVehicleIndex']);
    Route::get('lrtracking/create', [LegacyRedirectController::class, 'lrTrackingCreate']);
    Route::get('lrtracking/{tracking}/edit', [LegacyRedirectController::class, 'lrTrackingEdit']);
    Route::get('lrtracking/{tracking}', [LegacyRedirectController::class, 'lrTrackingShow']);
    Route::get('lrtracking', [LegacyRedirectController::class, 'lrTrackingIndex']);
    Route::get('delivered_list', [LegacyRedirectController::class, 'lrTrackingCompleted'])->name('delivered_list');
    Route::get('/epod', [LegacyRedirectController::class, 'epodIndex'])->name('epod');
    Route::get('epod/add_epod', [LegacyRedirectController::class, 'epodCreate'])->name('add_epod');
    Route::get('weight-correction/create', [LegacyRedirectController::class, 'weightCorrectionCreate']);
    Route::get('weight-correction/{weight}/edit', [LegacyRedirectController::class, 'weightCorrectionEdit']);
    Route::get('weight-correction', [LegacyRedirectController::class, 'weightCorrectionIndex']);
    Route::get('settings', [LegacyRedirectController::class, 'settings']);
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
