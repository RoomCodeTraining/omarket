<?php

use App\Http\Controllers\ArrivalController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\HomeController;
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

Route::post('/reservations', [ReservationController::class, 'store'])->name('reservations.store');
