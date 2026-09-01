<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ShippingAddressController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->name('api.auth.')->group(function () {
    Route::post('/send-otp', [AuthController::class, 'sendOtp'])
        ->middleware('throttle:5,1')
        ->name('send-otp');

    Route::post('/resend-otp', [AuthController::class, 'resendOtp'])
        ->middleware('throttle:5,1')
        ->name('resend-otp');

    Route::post('/verify-otp', [AuthController::class, 'verifyOtp'])
        ->middleware('throttle:10,1')
        ->name('verify-otp');

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', [AuthController::class, 'me'])->name('me');
        Route::put('/profile', [AuthController::class, 'updateProfile'])->name('profile');
        Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

        Route::prefix('shipping-addresses')->name('shipping-addresses.')->group(function () {
            Route::get('/', [ShippingAddressController::class, 'index'])->name('index');
            Route::post('/', [ShippingAddressController::class, 'store'])->name('store');
            Route::get('/{id}', [ShippingAddressController::class, 'show'])->name('show');
            Route::put('/{id}', [ShippingAddressController::class, 'update'])->name('update');
            Route::delete('/{id}', [ShippingAddressController::class, 'destroy'])->name('destroy');
            Route::post('/{id}/default', [ShippingAddressController::class, 'setDefault'])->name('default');
        });
    });
});
