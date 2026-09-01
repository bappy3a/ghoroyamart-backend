<?php

use App\Http\Controllers\Admin\Auth\LoginController;
use Illuminate\Support\Facades\Route;


Route::get('/login',[LoginController::class,'login'])->name('login');
Route::post('/login-process',[LoginController::class,'loginProcess'])->name('login.process');
Route::post('/logout',[LoginController::class,'logout'])->name('logout');