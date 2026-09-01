<?php

use Illuminate\Support\Facades\Route;

require __DIR__.'/admin/auth.php';

Route::prefix('backend')->middleware(['auth:web', 'is_admin', 'backend.permission'])->group(function(){
    require __DIR__.'/admin/dashboard.php';
    require __DIR__.'/admin/inventory.php';
    require __DIR__.'/admin/orders.php';
    require __DIR__.'/admin/reports.php';
    require __DIR__.'/admin/customers.php';
    require __DIR__.'/admin/acl.php';
});
