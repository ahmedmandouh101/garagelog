<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CarController;
use App\Http\Controllers\Api\GarageController;
use App\Http\Controllers\Api\ServiceRecordController;
use Illuminate\Support\Facades\Route;

// Public routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::get('/garages', [GarageController::class, 'index']);
Route::get('/garages/{garage}', [GarageController::class, 'show']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {

    // Auth
    Route::post('/logout', [AuthController::class, 'logout']);

    // Garages — admin only (handled inside FormRequest)
    Route::post('/garages', [GarageController::class, 'store']);
    Route::put('/garages/{garage}', [GarageController::class, 'update']);
    Route::delete('/garages/{garage}', [GarageController::class, 'destroy']);

    // Cars — owner only (handled inside FormRequest)
    Route::apiResource('cars', CarController::class);

    // Service Records — nested under cars
    Route::apiResource('cars.service-records', ServiceRecordController::class)
        ->except(['create', 'edit']);
});
