<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\SteadfastWebhookController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group.
|
*/

Route::post('/steadfast/webhook', SteadfastWebhookController::class)->name('api.steadfast.webhook');

require __DIR__.'/api/auth.php';
require __DIR__.'/api/products.php';
require __DIR__.'/api/categories.php';
require __DIR__.'/api/blogs.php';
require __DIR__.'/api/delivery-areas.php';
require __DIR__.'/api/sliders.php';
require __DIR__.'/api/home.php';
require __DIR__.'/api/promo.php';
require __DIR__.'/api/checkout.php';
require __DIR__.'/api/orders.php';
require __DIR__.'/api/wishlist.php';
require __DIR__.'/api/ai.php';
require __DIR__.'/api/settings.php';
require __DIR__.'/api/contact.php';
require __DIR__.'/api/custom-pages.php';
require __DIR__.'/api/chat.php';
