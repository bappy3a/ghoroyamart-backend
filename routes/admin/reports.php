<?php

use App\Http\Controllers\Admin\ProfitLossReportController;
use App\Http\Controllers\Admin\ModeratorOrderReportController;
use App\Http\Controllers\Admin\TotalOrderReportController;
use Illuminate\Support\Facades\Route;

Route::get('reports/profit-loss', ProfitLossReportController::class)->name('profit-loss-report.index');
Route::get('reports/moderator-orders', ModeratorOrderReportController::class)->name('moderator-order-report.index');
Route::get('reports/total-orders', TotalOrderReportController::class)->name('total-order-report.index');
