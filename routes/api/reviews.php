<?php

use App\Http\Controllers\Api\ReviewController;
use Illuminate\Support\Facades\Route;

Route::get('/products/{productId}/reviews', [ReviewController::class, 'productReviews'])
    ->whereNumber('productId')
    ->name('api.products.reviews');

Route::middleware('auth:sanctum')->prefix('reviews')->name('api.reviews.')->group(function () {
    Route::get('/', [ReviewController::class, 'index'])->name('index');
    Route::post('/', [ReviewController::class, 'store'])->middleware('throttle:20,1')->name('store');
    Route::get('/{id}', [ReviewController::class, 'show'])->whereNumber('id')->name('show');
    Route::match(['put', 'patch', 'post'], '/{id}', [ReviewController::class, 'update'])
        ->whereNumber('id')->middleware('throttle:20,1')->name('update');
    Route::delete('/{id}', [ReviewController::class, 'destroy'])->whereNumber('id')->name('destroy');
});
