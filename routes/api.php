<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\MeController;
use Illuminate\Support\Facades\Route;

Route::middleware(['throttle:auth', 'guest:sanctum'])
    ->prefix('auth')
    ->controller(AuthController::class)
    ->group(function () {
        Route::post('/register', 'register');
        Route::post('/login', 'login');
    });

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', MeController::class);
    Route::delete('/auth/logout', [AuthController::class, 'logout']);
});
