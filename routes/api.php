<?php

use App\Http\Controllers\Api\AccountSecurityController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CarController;
use App\Http\Controllers\Api\GarageController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\ReviewController;
use App\Http\Controllers\Api\ServiceReceiptController;
use App\Http\Controllers\Api\ServiceRecordController;
use App\Http\Controllers\Api\WorkshopController;
use App\Http\Controllers\Api\WorkshopCustomerController;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Public routes
Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:authentication');
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:authentication');
Route::post('/forgot-password', [AccountSecurityController::class, 'forgot'])->middleware('throttle:account-recovery');
Route::post('/reset-password', [AccountSecurityController::class, 'reset'])->middleware('throttle:account-recovery');
Route::post('/email/verification-notification', [AccountSecurityController::class, 'sendVerification'])
    ->middleware(['auth:sanctum', 'throttle:verification-email']);

Route::get('/garages', [GarageController::class, 'index']);
Route::get('/garages/{garage}', [GarageController::class, 'show']);

// Protected routes
Route::middleware(['auth:sanctum', 'verified'])->group(function () {
    Route::put('/garages/{garage}/manager', [WorkshopController::class, 'assignManager']);
    Route::get('/workshop/dashboard', [WorkshopController::class, 'dashboard']);
    Route::get('/workshop/staff', [WorkshopController::class, 'staff']);
    Route::post('/workshop/invitations', [WorkshopController::class, 'invite'])->middleware('throttle:authentication');
    Route::post('/workshop/invitations/accept', [WorkshopController::class, 'accept'])->middleware('throttle:authentication');
    Route::delete('/workshop/staff/{user}', [WorkshopController::class, 'removeStaff']);
    Route::get('/workshop/cars', [WorkshopController::class, 'cars']);
    Route::get('/workshop/services', [WorkshopController::class, 'services']);
    Route::get('/workshop/customers', [WorkshopCustomerController::class, 'index']);
    Route::post('/workshop/customers', [WorkshopCustomerController::class, 'store']);
    Route::post('/workshop/customers/{customer}/claim-token', [WorkshopCustomerController::class, 'renewToken'])->middleware('throttle:authentication');
    Route::post('/customer-links/claim', [WorkshopCustomerController::class, 'claim'])->middleware('throttle:authentication');
    Route::post('/customer-links/preview', [WorkshopCustomerController::class, 'preview'])->middleware('throttle:authentication');
    Route::get('/service-records/{serviceRecord}/receipt', [ServiceReceiptController::class, 'show']);
    Route::get('/cars/{car}/garage-access', [WorkshopController::class, 'access']);
    Route::post('/cars/{car}/garage-access/{garage}', [WorkshopController::class, 'grant']);
    Route::delete('/cars/{car}/garage-access/{garage}', [WorkshopController::class, 'revoke']);

    // Auth
    Route::get('/me', fn (Request $request) => new UserResource($request->user()))->withoutMiddleware('verified');
    Route::post('/logout', [AuthController::class, 'logout'])->withoutMiddleware('verified');

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
