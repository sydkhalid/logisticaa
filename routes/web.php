<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\HomeController;
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


Route::get('/register', function () {
    return view('pages.register');
});


Route::group(['middleware' => ['auth']], function () {
    //home
    Route::get('/home', [HomeController::class, 'index'])->name('home');
    //tracking LR
    Route::get('lrtracking',[HomeController::class, 'lrtracking'])->name('lrtracking');
    Route::get('lrtracking/add_lrtracking',[HomeController::class, 'add_lrtracking'])->name('add_lrtracking');
    Route::get('showlrtracking/{id$}',[HomeController::class, 'showlrtracking'])->name('showlrtracking');
    Route::post('ltrtracking_upload', [HomeController::class, 'ltrtracking_upload'])->name('ltrtracking_upload');

    Route::get('tracking_show/{id}',[HomeController::class, 'showlrtracking'])->name('showlrtracking');
    Route::get('/epod',[HomeController::class, 'epod'])->name('epod');
    Route::get('epod/add_epod',[HomeController::class, 'add_epod'])->name('add_epod');
    Route::post('epod/epod_upload', [HomeController::class, 'epod_upload'])->name('epod_upload');
    //logout
    Route::post('logout', [AuthController::class, 'logout'])->name('logout');
});
