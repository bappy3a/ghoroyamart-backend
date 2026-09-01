<?php

use App\Http\Controllers\Admin\OrderController;
use Illuminate\Support\Facades\Route;

Route::get('moderator-order-management', [OrderController::class, 'moderatorIndex'])->name('moderator-order-management.index');
Route::get('moderator-order-management/create', [OrderController::class, 'moderatorCreate'])->name('moderator-order-management.create');
Route::post('moderator-order-management', [OrderController::class, 'moderatorStore'])->name('moderator-order-management.store');

Route::get('orders', [OrderController::class, 'index'])->name('orders.index');
Route::get('orders/create', [OrderController::class, 'create'])->name('orders.create');
Route::post('orders', [OrderController::class, 'store'])->name('orders.store');
Route::get('orders/pending', [OrderController::class, 'status'])->defaults('status', 'pending')->name('orders.pending');
Route::get('orders/confirmed', [OrderController::class, 'status'])->defaults('status', 'confirmed')->name('orders.confirmed');
Route::get('orders/packaging', [OrderController::class, 'status'])->defaults('status', 'packaging')->name('orders.packaging');
Route::get('orders/processing', [OrderController::class, 'status'])->defaults('status', 'processing')->name('orders.processing');
Route::get('orders/shipped', [OrderController::class, 'status'])->defaults('status', 'shipped')->name('orders.shipped');
Route::get('orders/delivered', [OrderController::class, 'status'])->defaults('status', 'delivered')->name('orders.delivered');
Route::get('orders/cancelled', [OrderController::class, 'cancelled'])->name('orders.cancelled');
Route::post('orders/bulk-confirm', [OrderController::class, 'bulkConfirm'])->name('orders.update-bulk-confirm');
Route::post('orders/bulk-packaging', [OrderController::class, 'bulkMoveToPackaging'])->name('orders.update-bulk-packaging');
Route::get('orders/search', [OrderController::class, 'search'])->name('orders.search');
Route::get('orders/search/{order:order_number}', [OrderController::class, 'searchDetails'])->name('orders.search.details');
Route::put('orders/search/{order:order_number}/cancel', [OrderController::class, 'cancelFromSearch'])->name('orders.search.cancel');
Route::post('orders/{order}/restock', [OrderController::class, 'restock'])->name('orders.update-restock');
Route::post('orders/{order}/items/{item}/restock', [OrderController::class, 'restockItem'])->name('orders.update-item-restock');
Route::put('orders/{order}/items/{item}/cancel', [OrderController::class, 'cancelItem'])->name('orders.update-item-cancel');
Route::get('orders/{order:order_number}/edit', [OrderController::class, 'edit'])->name('orders.edit');
Route::put('orders/{order:order_number}', [OrderController::class, 'update'])->name('orders.update');
Route::get('orders/{order:order_number}', [OrderController::class, 'view'])->name('orders.view');
Route::get('orders/{order}/thermal-invoice/print', [OrderController::class, 'thermalInvoicePreview'])->name('orders.thermal-invoice.print');
Route::get('orders/{order}/invoice/print', [OrderController::class, 'deliveryReceiptPreview'])->name('orders.invoice.print');
Route::get('orders/{order}/delivery-receipt/print', [OrderController::class, 'deliveryReceiptPreview'])->name('orders.delivery-receipt.print');
Route::put('orders/{order}/status', [OrderController::class, 'updateStatus'])->name('orders.update-status');
