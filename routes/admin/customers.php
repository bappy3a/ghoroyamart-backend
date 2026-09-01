<?php

use App\Http\Controllers\Admin\CustomerController;
use Illuminate\Support\Facades\Route;

Route::get('customers', [CustomerController::class, 'index'])->name('customers.index');
Route::put('customers/{customer}', [CustomerController::class, 'update'])->name('customers.update');
