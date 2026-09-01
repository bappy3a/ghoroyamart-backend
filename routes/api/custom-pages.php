<?php

use App\Http\Controllers\Api\CustomPageController;
use Illuminate\Support\Facades\Route;

Route::prefix('custom-pages')->name('api.custom-pages.')->group(function () {
    Route::get('/', [CustomPageController::class, 'index'])->name('index');
    Route::get('/{slug}', [CustomPageController::class, 'show'])->name('show');
});
