<?php

use App\Http\Controllers\Admin\App\SettingsController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\VehicleController;
use App\Http\Controllers\LrtrackingController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\TrackController;
use App\Http\Controllers\VehicleotherController;
use App\Http\Controllers\WeightController;

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

Route::get('/', [AuthController::class, 'index'])->name('index');
Route::get('/login', [AuthController::class, 'index'])->name('index');
Route::post('/login', [AuthController::class, 'login'])->name('login');

Route::group(['middleware' => ['auth']], function () {
    //home
      abort(503, 'Application update required');
    Route::get('/home', [HomeController::class, 'index'])->name('home');
    // vehicle
    Route::resource('vehicle', VehicleController::class);
    Route::resource('vehicleOther', VehicleotherController::class);
    Route::post('fetch_vehicles', [VehicleController::class, 'fetch_vehicles'])->name('fetch_vehicles');
    Route::get('vehicleStopTrack/{id}', [VehicleotherController::class, 'vehicleStopTrack'])->name('vehicleStopTrack');
    Route::get('vehiclecheckTrack/{id}', [VehicleotherController::class, 'vehiclecheckTrack'])->name('vehiclecheckTrack');

    //tracking LR
    Route::resource('lrtracking', LrtrackingController::class);
    Route::get('delivered_list',[LrtrackingController::class, 'delivered_list'])->name('delivered_list');
    Route::get('lrtracking_again/{vehicle_no}/{lrnumber}', [TrackController::class, 'getsinglelrtracking'])->name('getsinglelrtracking');
    Route::get('/epod',[HomeController::class, 'epod'])->name('epod');
    Route::get('epod/add_epod',[HomeController::class, 'add_epod'])->name('add_epod');
    Route::post('epod/epod_upload', [HomeController::class, 'epod_upload'])->name('epod_upload');
    //weight correction
    Route::resource('weight-correction', WeightController::class);
    Route::post('fetchlr', [WeightController::class, 'fetchlr'])->name('fetchlr');
    //logout
    Route::post('logout', [AuthController::class, 'logout'])->name('logout');
    //settings
    Route::resource('settings', SettingController::class);
    Route::post('settings/save', 'Admin\App\SettingsController@save');
});
