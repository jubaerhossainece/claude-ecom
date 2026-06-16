<?php

use App\Http\Controllers\Auth\CustomerAuthController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\WishlistController;
use Illuminate\Support\Facades\Route;

// Storefront
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/search', [ProductController::class, 'search'])->name('products.search');
Route::get('/category/{category:slug}', [ProductController::class, 'category'])->name('products.category');
Route::get('/product/{product:slug}', [ProductController::class, 'show'])->name('products.show');

// Cart (Livewire handles most, but we need these for non-JS fallback)
Route::post('/cart/add', [CartController::class, 'add'])->name('cart.add');
Route::patch('/cart/update/{item}', [CartController::class, 'update'])->name('cart.update');
Route::delete('/cart/remove/{item}', [CartController::class, 'remove'])->name('cart.remove');
Route::get('/cart', [CartController::class, 'index'])->name('cart.index');

// Checkout
Route::get('/checkout', [CheckoutController::class, 'index'])->name('checkout.index');
Route::post('/checkout', [CheckoutController::class, 'store'])->name('checkout.store');
Route::get('/checkout/success/{order}', [CheckoutController::class, 'success'])->name('checkout.success');

// Auth — phone/OTP
Route::middleware('guest:customer')->group(function () {
    Route::get('/login', [CustomerAuthController::class, 'showLogin'])->name('customer.login');
    Route::post('/login/send-otp', [CustomerAuthController::class, 'sendOtp'])->name('customer.send-otp');
    Route::post('/login/verify-otp', [CustomerAuthController::class, 'verifyOtp'])->name('customer.verify-otp');
    Route::get('/register', [CustomerAuthController::class, 'showRegister'])->name('customer.register');
    Route::post('/register', [CustomerAuthController::class, 'register'])->name('customer.register.store');
});

Route::post('/logout', [CustomerAuthController::class, 'logout'])
    ->name('customer.logout')
    ->middleware('auth:customer');

// Customer account (auth required)
Route::middleware('auth:customer')->prefix('account')->name('account.')->group(function () {
    Route::get('/', [OrderController::class, 'index'])->name('index');
    Route::get('/orders', [OrderController::class, 'index'])->name('orders');
    Route::get('/orders/{order}', [OrderController::class, 'show'])->name('orders.show');
    Route::post('/orders/{order}/reorder', [OrderController::class, 'reorder'])->name('orders.reorder');
    Route::get('/wishlist', [WishlistController::class, 'index'])->name('wishlist');
    Route::post('/wishlist/{product}', [WishlistController::class, 'toggle'])->name('wishlist.toggle');
});

// API endpoints for Livewire/AJAX
Route::get('/api/districts/{division}', function (App\Models\Division $division) {
    return $division->districts()->select('id', 'name', 'bn_name')->orderBy('name')->get();
})->name('api.districts');

Route::get('/api/thanas/{district}', function (App\Models\District $district) {
    return $district->thanas()->select('id', 'name', 'bn_name')->orderBy('name')->get();
})->name('api.thanas');
