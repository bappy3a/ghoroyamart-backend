<?php

use App\Http\Controllers\Api\OrderController;
use Illuminate\Support\Facades\Route;

Route::post('/orders/track', [OrderController::class, 'track'])
    ->middleware('throttle:20,1')
    ->name('api.orders.track');

Route::middleware('auth:sanctum')->prefix('orders')->name('api.orders.')->group(function () {
    Route::get('/', [OrderController::class, 'index'])->name('index');
    Route::get('/{orderNumber}/invoice', [OrderController::class, 'invoice'])->name('invoice');
    Route::get('/{orderNumber}', [OrderController::class, 'show'])->name('show');
});
