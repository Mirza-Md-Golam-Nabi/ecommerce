<?php

use App\Http\Controllers\Admin\OrderInvoiceController;
use App\Http\Controllers\Storefront\Auth\LoginController;
use App\Http\Controllers\Storefront\Auth\RegisterController;
use App\Http\Controllers\Storefront\BagController;
use App\Http\Controllers\Storefront\CatalogController;
use App\Http\Controllers\Storefront\CheckoutController;
use App\Http\Controllers\Storefront\HomeController;
use App\Http\Controllers\Storefront\OrderController;
use App\Http\Controllers\Storefront\SearchController;
use App\Rules\UniqueStoreSlug;
use Illuminate\Support\Facades\Route;

/*
| Storefront. Any new top-level path segment must be added to
| App\Rules\UniqueStoreSlug::RESERVED so no category/product can take it.
*/

Route::get('/', HomeController::class)->name('home');
Route::get('/search', SearchController::class)->name('search');

Route::get('/bag', [BagController::class, 'show'])->name('bag.show');
Route::put('/bag/items/{product}', [BagController::class, 'update'])
    ->withTrashed()
    ->middleware('throttle:120,1')
    ->name('bag.items.update');

Route::get('/quick-view/{slug}', [CatalogController::class, 'quickView'])->name('catalog.quick-view');

Route::middleware('guest:customer')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->middleware('throttle:10,1')->name('login.store');
    Route::get('/register', [RegisterController::class, 'create'])->name('register');
    Route::post('/register', [RegisterController::class, 'store'])->middleware('throttle:register')->name('register.store');
});

Route::middleware('auth:customer')->group(function () {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

    Route::get('/checkout', [CheckoutController::class, 'show'])->name('checkout');
    Route::post('/checkout', [CheckoutController::class, 'store'])->middleware('throttle:place-order')->name('checkout.store');

    Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/{order}', [OrderController::class, 'show'])->name('orders.show');
    Route::post('/orders/{order}/cancel', [OrderController::class, 'cancel'])->name('orders.cancel');
});

/* Admin-only printable invoice (auth checked in the controller against the Filament `web` guard). */
Route::get('/admin/orders/{order}/invoice', OrderInvoiceController::class)->name('admin.orders.invoice');

/* Must stay last: flat category/product URLs like /spices or /radhuni-holud-gura-200g. */
Route::get('/{slug}', [CatalogController::class, 'show'])
    ->where('slug', UniqueStoreSlug::PATTERN)
    ->name('catalog.show');
