<?php

use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DriverController;
use App\Http\Controllers\ServiceAreaController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes for GeoDispatch Spatial Backend
|--------------------------------------------------------------------------
*/

// UC-01: Find Nearby Drivers (within radius)
Route::get('drivers/nearby', [DriverController::class, 'nearby']);

// UC-02: Find Nearest Available Driver (KNN Proximity)
Route::get('drivers/nearest', [DriverController::class, 'nearest']);

// UC-03: Check if a point belongs to any Service Area (Containment check)
Route::get('service-areas/check', [ServiceAreaController::class, 'check']);

// CRUD REST Resources
Route::apiResource('drivers', DriverController::class);
Route::apiResource('customers', CustomerController::class);
Route::apiResource('service-areas', ServiceAreaController::class);
