<?php

use App\Http\Controllers\Api\SocialAuthController;
use App\Http\Controllers\PublicCustomPageController;
use Illuminate\Support\Facades\Route;

require __DIR__.'/admin.php';

Route::get('/sms', function () {
    $message = "Your Agonito verification code is 772322. Use this code to verify your account.Please do not share this code with anyone.";
    $data =  smsSend($message, '1721209595', '880');
    dd($data);
    return 'SMS sent successfully';
});


Route::prefix('auth/{provider}')
    ->whereIn('provider', ['google', 'facebook'])
    ->group(function () {
        Route::get('/redirect', [SocialAuthController::class, 'redirect'])
            ->middleware('throttle:20,1')
            ->name('social.redirect');
        Route::get('/callback', [SocialAuthController::class, 'callback'])
            ->middleware('throttle:20,1')
            ->name('social.callback');
    });

$frontendRemoved = static fn () => response()->json([
    'message' => 'The public frontend has been removed.',
], 404);

Route::redirect('/', '/backend')->name('home');
Route::get('/products', $frontendRemoved)->name('products');
Route::get('/flash-deals', $frontendRemoved)->name('flash.deals');
Route::get('/blog', $frontendRemoved)->name('blog.index');
Route::get('/about-us', $frontendRemoved)->name('about.us');
Route::get('/contact-us', $frontendRemoved)->name('contact.us');
Route::get('/faq', $frontendRemoved)->name('faq');
Route::get('/reviews', $frontendRemoved)->name('reviews');
Route::get('/promo/{slug}', $frontendRemoved)->name('promo.landing.show');

Route::get('/pages/{slug}', [PublicCustomPageController::class, 'show'])
    ->name('pages.show');

Route::get('/customer/login', $frontendRemoved)->name('customer.login');
Route::get('/customer/register', $frontendRemoved)->name('customer.register');
Route::get('/customer/wishlist', $frontendRemoved)->name('customer.wishlist');
Route::get('/cart', $frontendRemoved)->name('cart.index');
Route::get('/checkout', $frontendRemoved)->name('checkout.index');
