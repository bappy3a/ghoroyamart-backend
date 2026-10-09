<?php

use App\Http\Controllers\Api\CheckoutController;
use App\Http\Controllers\Api\GuestCheckoutController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->prefix('checkout')->name('api.checkout.')->group(function () {
    Route::get('/delivery-charges', [CheckoutController::class, 'deliveryCharges'])
        ->name('delivery-charges');

    Route::post('/preview', [CheckoutController::class, 'preview'])
        ->middleware('throttle:60,1')
        ->name('preview');

    Route::post('/coupon/apply', [CheckoutController::class, 'applyCoupon'])
        ->middleware('throttle:30,1')
        ->name('coupon.apply');

    Route::post('/coupon/remove', [CheckoutController::class, 'removeCoupon'])
        ->middleware('throttle:30,1')
        ->name('coupon.remove');

    Route::post('/', [CheckoutController::class, 'store'])
        ->middleware('throttle:20,1')
        ->name('store');
});

// Guest checkout: no account required.
Route::prefix('checkout/guest')->name('api.checkout.guest.')->group(function () {
    Route::post('/preview', [GuestCheckoutController::class, 'preview'])
        ->middleware('throttle:60,1')->name('preview');
    Route::post('/coupon/apply', [GuestCheckoutController::class, 'applyCoupon'])
        ->middleware('throttle:30,1')->name('coupon.apply');
    Route::post('/coupon/remove', [GuestCheckoutController::class, 'removeCoupon'])
        ->middleware('throttle:30,1')->name('coupon.remove');
    Route::post('/', [GuestCheckoutController::class, 'store'])
        ->middleware('throttle:20,1')->name('store');
});
