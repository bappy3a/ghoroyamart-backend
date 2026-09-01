<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ContactMessageController;
use App\Http\Controllers\Admin\HomePageSettingController;
use App\Http\Controllers\Admin\SettingsController;
use Illuminate\Support\Facades\Route;

Route::get('/',[DashboardController::class,'index'])->name('dashboard');
Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
Route::put('/settings', [SettingsController::class, 'update'])->name('settings.update');
Route::get('/home-page-settings', [HomePageSettingController::class, 'index'])->name('home-page-settings.index');
Route::put('/home-page-settings', [HomePageSettingController::class, 'update'])->name('home-page-settings.update');
Route::get('/contact-messages', [ContactMessageController::class, 'index'])->name('contact-messages.index');
Route::get('/contact-messages/{contactMessage}', [ContactMessageController::class, 'show'])->name('contact-messages.show');
Route::put('/contact-messages/{contactMessage}/status', [ContactMessageController::class, 'updateStatus'])->name('contact-messages.status');
