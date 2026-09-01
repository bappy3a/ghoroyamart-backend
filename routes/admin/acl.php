<?php

use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\Admin\RoleController;
use Illuminate\Support\Facades\Route;

Route::resource('roles', RoleController::class)->except(['show']);
Route::get('permissions', [PermissionController::class, 'index'])->name('permissions.index');
