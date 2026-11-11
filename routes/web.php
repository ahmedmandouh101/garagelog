<?php

use App\Http\Controllers\Api\AccountSecurityController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'landing');
Route::view('/app', 'workshop')->name('workspace');
Route::view('/demo', 'demo')->name('demo');
Route::get('/verify-email/{id}/{hash}', [AccountSecurityController::class, 'verify'])
    ->middleware(['signed:relative', 'throttle:10,1'])->name('verification.verify');
