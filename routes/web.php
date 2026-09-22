<?php

use App\Http\Controllers\Auth\CustomerAuthController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\CouponController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReturnRequestController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\WishlistController;
use App\Models\District;
use App\Models\Division;
use App\Models\Order;
use App\Models\Store;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

// Storefront (page views tracked for the conversion-rate dashboard stat)
Route::middleware('track.visit')->group(function () {
    Route::get('/', [HomeController::class, 'index'])->name('home');
    Route::get('/search', [ProductController::class, 'search'])->name('products.search');
    Route::get('/category/{category:slug}', [ProductController::class, 'category'])->name('products.category');
    Route::get('/product/{product:slug}', [ProductController::class, 'show'])->name('products.show');
    Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
    Route::get('/checkout', [CheckoutController::class, 'index'])->name('checkout.index');
    Route::get('/checkout/success/{order}', [CheckoutController::class, 'success'])->name('checkout.success');
});

// Cart (Livewire handles most, but we need these for non-JS fallback)
Route::post('/cart/add', [CartController::class, 'add'])->name('cart.add');
Route::patch('/cart/update/{item}', [CartController::class, 'update'])->name('cart.update');
Route::delete('/cart/remove/{item}', [CartController::class, 'remove'])->name('cart.remove');

// Checkout
Route::post('/checkout', [CheckoutController::class, 'store'])->name('checkout.store');

// Auth — phone/OTP
Route::middleware('guest:customer')->group(function () {
    Route::get('/login', [CustomerAuthController::class, 'showLogin'])->name('customer.login');
    Route::post('/login/send-otp', [CustomerAuthController::class, 'sendOtp'])->middleware('throttle:6,1')->name('customer.send-otp');
    Route::post('/login/verify-otp', [CustomerAuthController::class, 'verifyOtp'])->middleware('throttle:10,1')->name('customer.verify-otp');
    Route::get('/register', [CustomerAuthController::class, 'showRegister'])->name('customer.register');
    Route::post('/register', [CustomerAuthController::class, 'register'])->middleware('throttle:6,1')->name('customer.register.store');
});

Route::post('/logout', [CustomerAuthController::class, 'logout'])
    ->name('customer.logout')
    ->middleware('auth:customer');

// Reviews (product-scoped, not under /account)
Route::middleware('auth:customer')->group(function () {
    Route::post('/product/{product}/reviews', [ReviewController::class, 'store'])->name('reviews.store');
    Route::put('/reviews/{review}', [ReviewController::class, 'update'])->name('reviews.update');
    Route::delete('/reviews/{review}', [ReviewController::class, 'destroy'])->name('reviews.destroy');
});

// Customer account (auth required)
Route::middleware('auth:customer')->prefix('account')->name('account.')->group(function () {
    Route::get('/', [OrderController::class, 'index'])->name('index');
    Route::get('/orders', [OrderController::class, 'index'])->name('orders');
    Route::get('/orders/{order}', [OrderController::class, 'show'])->name('orders.show');
    Route::get('/orders/{order}/invoice', [OrderController::class, 'invoice'])->name('orders.invoice');
    Route::post('/orders/{order}/reorder', [OrderController::class, 'reorder'])->name('orders.reorder');
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications');
    Route::get('/returns', [ReturnRequestController::class, 'index'])->name('returns');
    Route::post('/order-items/{orderItem}/return-request', [ReturnRequestController::class, 'store'])->name('return-requests.store');
    Route::get('/wishlist', [WishlistController::class, 'index'])->name('wishlist');
    Route::post('/wishlist/{product}', [WishlistController::class, 'toggle'])->name('wishlist.toggle');
    Route::post('/wishlist/{product}/move-to-cart', [WishlistController::class, 'moveToCart'])->name('wishlist.move-to-cart');
    Route::get('/coupons', [CouponController::class, 'index'])->name('coupons');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
});

// Admin-only report downloads (Filament panel itself is registered separately)
Route::middleware('auth:web')->prefix('admin')->name('admin.')->group(function () {
    Route::get('/inventory-report/export', function () {
        $store = Store::current();

        $rows = DB::table('warehouse_stocks')
            ->join('products', 'products.id', '=', 'warehouse_stocks.product_id')
            ->join('warehouses', 'warehouses.id', '=', 'warehouse_stocks.warehouse_id')
            ->where('products.store_id', $store->id)
            ->select(
                'warehouses.name as warehouse_name',
                DB::raw('SUM(warehouse_stocks.quantity) as on_hand'),
                DB::raw('SUM(warehouse_stocks.reserved_quantity) as reserved'),
                DB::raw('SUM(warehouse_stocks.quantity * COALESCE(products.cost_price, 0)) as value'),
                DB::raw('COUNT(DISTINCT warehouse_stocks.product_id) as product_count')
            )
            ->groupBy('warehouses.id', 'warehouses.name')
            ->orderBy('warehouses.name')
            ->get();

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Warehouse', 'Products', 'On Hand', 'Reserved', 'Value (BDT)']);
            foreach ($rows as $row) {
                fputcsv($out, [$row->warehouse_name, $row->product_count, $row->on_hand, $row->reserved, $row->value]);
            }
            fclose($out);
        }, 'inventory-report-'.now()->format('Y-m-d').'.csv');
    })->name('inventory-report.export');

    Route::get('/orders/{order}/invoice', function (Order $order) {
        $order->load('items', 'store');

        return view('orders.invoice', ['order' => $order]);
    })->name('orders.invoice');
});

// API endpoints for Livewire/AJAX
Route::get('/api/search-autocomplete', [ProductController::class, 'autocomplete'])->name('products.autocomplete');

Route::get('/api/districts/{division}', function (Division $division) {
    return $division->districts()->select('id', 'name', 'bn_name')->orderBy('name')->get();
})->name('api.districts');

Route::get('/api/thanas/{district}', function (District $district) {
    return $district->thanas()->select('id', 'name', 'bn_name')->orderBy('name')->get();
})->name('api.thanas');
