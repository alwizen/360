<?php

use Illuminate\Support\Facades\Route;

// Route::get('/', function () {
//     return view('welcome');
// });
use App\Http\Controllers\TapController;

Route::get('/tap', [TapController::class, 'index'])->name('tap.index');
Route::get('/tap/active', [TapController::class, 'getActivePretrip'])->name('tap.active');
Route::post('/tap/process', [TapController::class, 'processTap'])->name('tap.process');
Route::post('/tap/reset', [TapController::class, 'resetSession'])->name('tap.reset');
