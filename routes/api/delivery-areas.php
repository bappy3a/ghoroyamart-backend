<?php

use App\Http\Controllers\Api\DeliveryAreaController;
use Illuminate\Support\Facades\Route;

Route::prefix('delivery-areas')->name('api.delivery-areas.')->group(function () {
    Route::get('/', [DeliveryAreaController::class, 'index'])->name('index');
});
