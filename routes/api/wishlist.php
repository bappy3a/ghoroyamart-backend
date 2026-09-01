<?php

use App\Http\Controllers\Api\WishlistController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->prefix('wishlist')->name('api.wishlist.')->group(function () {
    Route::get('/', [WishlistController::class, 'index'])->name('index');
    Route::post('/', [WishlistController::class, 'store'])
        ->middleware('throttle:60,1')
        ->name('store');
    Route::post('/toggle', [WishlistController::class, 'toggle'])
        ->middleware('throttle:60,1')
        ->name('toggle');
    Route::delete('/{productId}', [WishlistController::class, 'destroy'])
        ->whereNumber('productId')
        ->middleware('throttle:60,1')
        ->name('destroy');
});
