<?php

use App\Http\Controllers\Api\PretripController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');

/*
|--------------------------------------------------------------------------
| API Routes - PreTrip System
|--------------------------------------------------------------------------
*/

// Truck RFID Points
Route::get('trucks/{truck_id}/rfid-points', [PretripController::class, 'getTruckRfidPoints']);
Route::get('trucks/{truck_id}/today-pretrip', [PretripController::class, 'getTodayPretrip']);

// PreTrip - Step by Step (Real-time tapping)
Route::post('pretrips/start', [PretripController::class, 'startPretrip']);
Route::post('pretrips/{pretrip_id}/tap-point', [PretripController::class, 'tapPoint']);
Route::post('pretrips/{pretrip_id}/tap-rfid', [PretripController::class, 'tapRfid']);
Route::post('pretrips/{pretrip_id}/complete', [PretripController::class, 'markComplete']);

// PreTrip - Complete (All at once)
Route::post('pretrips/complete', [PretripController::class, 'completePretrip']);

// PreTrip - View
Route::get('pretrips/{pretrip_id}', [PretripController::class, 'show']);
