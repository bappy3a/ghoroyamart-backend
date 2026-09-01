<?php

use App\Http\Controllers\Api\ContactController;
use Illuminate\Support\Facades\Route;

Route::prefix('contact')->name('api.contact.')->group(function () {
    Route::post('/', [ContactController::class, 'store'])->name('store');
});
