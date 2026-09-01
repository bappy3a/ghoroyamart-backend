<?php

use App\Http\Controllers\Api\ProductController;
use Illuminate\Support\Facades\Route;

Route::prefix('products')->name('api.products.')->group(function () {
    Route::get('/', [ProductController::class, 'index'])->name('index');
    // Product details — accepts numeric id or slug (frontend: /product/[id])
    Route::get('/{id}', [ProductController::class, 'show'])->name('show');
});
