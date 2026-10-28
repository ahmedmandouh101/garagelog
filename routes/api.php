<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CarController;
use App\Http\Controllers\Api\GarageController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\ReviewController;
use App\Http\Controllers\Api\ServiceRecordController;
use App\Http\Controllers\Api\WorkshopController;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Public routes
Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:authentication');
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:authentication');

Route::get('/garages', [GarageController::class, 'index']);
Route::get('/garages/{garage}', [GarageController::class, 'show']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    Route::put('/garages/{garage}/manager', [WorkshopController::class, 'assignManager']);
    Route::get('/workshop/dashboard', [WorkshopController::class, 'dashboard']);
    Route::get('/workshop/staff', [WorkshopController::class, 'staff']);
    Route::post('/workshop/invitations', [WorkshopController::class, 'invite'])->middleware('throttle:authentication');
    Route::post('/workshop/invitations/accept', [WorkshopController::class, 'accept'])->middleware('throttle:authentication');
    Route::delete('/workshop/staff/{user}', [WorkshopController::class, 'removeStaff']);
    Route::get('/workshop/cars', [WorkshopController::class, 'cars']);
    Route::post('/cars/{car}/garage-access/{garage}', [WorkshopController::class, 'grant']);
    Route::delete('/cars/{car}/garage-access/{garage}', [WorkshopController::class, 'revoke']);

    // Auth
    Route::get('/me', fn (Request $request) => new UserResource($request->user()));
    Route::post('/logout', [AuthController::class, 'logout']);

    // Garages — admin only
    Route::post('/garages', [GarageController::class, 'store']);
    Route::put('/garages/{garage}', [GarageController::class, 'update']);
    Route::delete('/garages/{garage}', [GarageController::class, 'destroy']);

    // Cars — owner only
    Route::apiResource('cars', CarController::class);

    // Service Records — nested under cars
    Route::apiResource('cars.service-records', ServiceRecordController::class)
        ->parameters(['service-records' => 'serviceRecord'])
        ->scoped()
        ->except(['create', 'edit']);

    // Reviews — nested under cars and service records
    Route::post('cars/{car}/service-records/{serviceRecord}/reviews', [ReviewController::class, 'store']);
    Route::get('cars/{car}/service-records/{serviceRecord}/reviews', [ReviewController::class, 'index']);

    // Mechanic reviews
    Route::get('/mechanic/reviews', [ReviewController::class, 'mechanicReviews']);

    // Notifications
    Route::prefix('notifications')->group(function () {
        Route::get('/', [NotificationController::class, 'index']);
        Route::get('/unread', [NotificationController::class, 'unread']);
        Route::post('/{id}/read', [NotificationController::class, 'markAsRead']);
        Route::post('/read-all', [NotificationController::class, 'markAllAsRead']);
    });
});
