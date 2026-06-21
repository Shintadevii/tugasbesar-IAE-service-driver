<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\AgentController;
use App\Http\Controllers\Api\DriverController;
use App\Http\Controllers\Api\AssignmentController; // Diubah ke AssignmentController agar sinkron dengan fungsi updateStatus yang baru

/*
|--------------------------------------------------------------------------
| AUTH
|--------------------------------------------------------------------------
*/
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/logout', [AuthController::class, 'logout']);
    });
});

/*
|--------------------------------------------------------------------------
| CUSTOMER & AGENT
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/customers', [CustomerController::class, 'store']);
    Route::get('/customers', [CustomerController::class, 'index']);
    Route::get('/customers/{id}', [CustomerController::class, 'show']);

    Route::post('/agents', [AgentController::class, 'store']);
    Route::get('/agents', [AgentController::class, 'index']);
    Route::get('/agents/{id}', [AgentController::class, 'show']);
});

/*
|--------------------------------------------------------------------------
| DRIVER MANAGEMENT (Driver Service - REST API)
|--------------------------------------------------------------------------
*/
Route::prefix('drivers')->group(function () {
    // Public endpoints - no auth required for demo purposes
    Route::get('/', [DriverController::class, 'index']);
    Route::get('/available', [DriverController::class, 'available']);
    Route::get('/{id}', [DriverController::class, 'show']);
    Route::post('/', [DriverController::class, 'store']);
    Route::put('/{id}', [DriverController::class, 'update']);
    Route::delete('/{id}', [DriverController::class, 'destroy']);
});

/*
|--------------------------------------------------------------------------
| DRIVER ASSIGNMENTS
|--------------------------------------------------------------------------
*/
Route::prefix('assignments')->group(function () {
    // Diarahkan penuh ke AssignmentController yang sudah dilengkapi fungsi dan validasi enum database
    Route::get('/', [AssignmentController::class, 'index']);
    Route::get('/tracking/{trackingNumber}', [AssignmentController::class, 'getByTrackingNumber']);
    Route::get('/driver/{driverId}', [AssignmentController::class, 'getByDriver']);
    Route::patch('/{id}/status', [AssignmentController::class, 'updateStatus']);
});