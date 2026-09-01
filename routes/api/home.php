<?php

use App\Http\Controllers\Api\FlashSaleController;
use App\Http\Controllers\Api\HomeController;
use Illuminate\Support\Facades\Route;

Route::prefix('home')->name('api.home.')->group(function () {
    Route::get('/', [HomeController::class, 'index'])->name('index');

    // Flash sale page — all live/upcoming rounds with products
    Route::get('/flash-sale', [FlashSaleController::class, 'index'])->name('flash-sale');
    Route::get('/flash-sale/{id}', [FlashSaleController::class, 'show'])->name('flash-sale.show');
});
