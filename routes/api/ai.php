<?php

use App\Http\Controllers\Api\AiController;
use Illuminate\Support\Facades\Route;

Route::prefix('ai')->name('api.ai.')->group(function () {
    Route::match(['get', 'post'], '/image-search', [AiController::class, 'imageSearch'])->name('image-search');
    Route::match(['get', 'post'], '/transcribe', [AiController::class, 'transcribe'])->name('transcribe');
    Route::match(['get', 'post'], '/chat', [AiController::class, 'chat'])->name('chat');
});
