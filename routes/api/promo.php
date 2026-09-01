<?php

use App\Http\Controllers\Api\PromoCheckoutController;
use Illuminate\Support\Facades\Route;

Route::prefix('promo')->name('api.promo.')->group(function () {
    // Get promotion page with product
    Route::get('/{slug}', [PromoCheckoutController::class, 'getPromoPage'])->name('get-page');

    // Get product variants
    Route::get('/product/{productId}/variants', [PromoCheckoutController::class, 'getProductVariants'])->name('product-variants');

    // Get product by variant selection
    Route::post('/product/{productId}/by-variant', [PromoCheckoutController::class, 'getProductByVariant'])->name('get-by-variant');

    // Get divisions for shipping form
    Route::get('/divisions/all', [PromoCheckoutController::class, 'getDivisions'])->name('divisions');

    // Checkout routes (guest checkout - no auth required)
    // These routes need session state for OTP verification, so run them with the `web` middleware.
    Route::prefix('checkout')->name('checkout.')->middleware('web')->group(function () {
        // Send OTP
        Route::post('/send-otp', [PromoCheckoutController::class, 'sendOtp'])
            ->middleware('throttle:3,1')
            ->name('send-otp');

        // Verify OTP
        Route::post('/verify-otp', [PromoCheckoutController::class, 'verifyOtp'])
            ->middleware('throttle:5,1')
            ->name('verify-otp');

        // Place order
        Route::post('/place-order', [PromoCheckoutController::class, 'placeOrder'])
            ->name('place-order');
    });
});
