<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\EmailVerificationController;
use App\Http\Controllers\MeController;
use App\Http\Controllers\WalletController;
use Illuminate\Support\Facades\Route;

Route::middleware(['throttle:auth', 'guest:sanctum'])
    ->prefix('auth')
    ->controller(AuthController::class)
    ->group(function () {
        Route::post('/register', 'register');
        Route::post('/login', 'login');
    });

Route::prefix('email')->controller(EmailVerificationController::class)->group(function () {
    Route::get('/verify/{id}/{hash}', 'verify')->middleware(['signed', 'throttle:auth'])->name('verification.verify');
    Route::post('/verification-notification', 'resend')->middleware(['auth:sanctum', 'throttle:auth']);
});

Route::middleware(['auth:sanctum', 'verified'])->group(function () {
    Route::get('/me', MeController::class);
    Route::delete('/auth/logout', [AuthController::class, 'logout']);

    Route::prefix('wallet')
        ->controller(WalletController::class)
        ->group(function () {
            Route::get('/', 'show');
            Route::post('/create', 'create');
        });
});
