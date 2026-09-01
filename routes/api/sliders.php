<?php

use App\Http\Controllers\Api\SliderController;
use Illuminate\Support\Facades\Route;

Route::prefix('sliders')->name('api.sliders.')->group(function () {
    Route::get('/', [SliderController::class, 'index'])->name('index');
});
