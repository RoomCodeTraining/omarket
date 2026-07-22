<?php

use App\Http\Controllers\ArrivalController;
use App\Http\Controllers\Auth\ClientAuthController;
use App\Http\Controllers\Auth\PartnerAuthController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\ClientAccountController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\Partner\PartnerDashboardController;
use App\Http\Controllers\ReservationController;
use App\Http\Controllers\ShopController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/boutique', [ShopController::class, 'index'])->name('shop.index');
Route::get('/arrivages', [ArrivalController::class, 'index'])->name('arrivals.index');

Route::get('/courses', [CourseController::class, 'index'])->name('courses.index');
Route::post('/courses', [CourseController::class, 'store'])->name('courses.store');

Route::get('/panier', [CartController::class, 'index'])->name('cart.index');
Route::post('/panier', [CartController::class, 'store'])->name('cart.store');
Route::patch('/panier/{productId}', [CartController::class, 'update'])->name('cart.update');
Route::delete('/panier/{productId}', [CartController::class, 'destroy'])->name('cart.destroy');
Route::post('/panier/commander', [CartController::class, 'checkout'])->name('cart.checkout');

Route::get('/commandes/{order}', [OrderController::class, 'show'])->name('orders.show');

Route::post('/reservations', [ReservationController::class, 'store'])->name('reservations.store');

Route::middleware('guest')->group(function () {
    Route::redirect('/login', '/connexion')->name('login');

    Route::get('/connexion', [ClientAuthController::class, 'showLogin'])->name('client.login');
    Route::post('/connexion', [ClientAuthController::class, 'login'])->name('client.login.store');

    Route::get('/partenaires/inscription', [PartnerAuthController::class, 'showRegister'])
        ->name('partner.register');
    Route::post('/partenaires/inscription', [PartnerAuthController::class, 'register'])
        ->name('partner.register.store');
    Route::get('/partenaires/connexion', [PartnerAuthController::class, 'showLogin'])
        ->name('partner.login');
    Route::post('/partenaires/connexion', [PartnerAuthController::class, 'login'])
        ->name('partner.login.store');
});

Route::post('/deconnexion', [ClientAuthController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

Route::post('/partenaires/deconnexion', [PartnerAuthController::class, 'logout'])
    ->middleware('auth')
    ->name('partner.logout');

Route::middleware('auth')->group(function () {
    Route::get('/compte', [ClientAccountController::class, 'index'])->name('account.index');
});

Route::middleware(['auth', 'partner'])->prefix('partenaires')->name('partner.')->group(function () {
    Route::get('/espace', [PartnerDashboardController::class, 'index'])->name('dashboard');
    Route::post('/espace/produits/image', [PartnerDashboardController::class, 'storeProductImage'])
        ->name('products.image.store');
    Route::post('/espace/produits/image/chunk', [PartnerDashboardController::class, 'storeProductImageChunk'])
        ->name('products.image.chunk');
    Route::delete('/espace/produits/image', [PartnerDashboardController::class, 'destroyProductImage'])
        ->name('products.image.destroy');
    Route::post('/espace/produits', [PartnerDashboardController::class, 'storeProduct'])->name('products.store');
    Route::put('/espace/produits/{product}', [PartnerDashboardController::class, 'updateProduct'])
        ->name('products.update');
    Route::post('/espace/produits/{product}/soumettre', [PartnerDashboardController::class, 'submitProduct'])
        ->name('products.submit');
    Route::post('/espace/produits/{product}/depublier', [PartnerDashboardController::class, 'unpublishProduct'])
        ->name('products.unpublish');

    // Old URLs / browser retry after a failed multipart POST
    Route::redirect('/produits', '/partenaires/espace');
});
