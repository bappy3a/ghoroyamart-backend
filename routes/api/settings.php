<?php

use App\Http\Controllers\Api\SettingController;
use Illuminate\Support\Facades\Route;

Route::prefix('settings')->name('api.settings.')->group(function () {
    Route::get('/', [SettingController::class, 'index'])->name('index');
});
