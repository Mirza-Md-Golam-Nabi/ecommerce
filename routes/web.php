<?php

use App\Http\Controllers\Storefront\BagController;
use App\Http\Controllers\Storefront\CatalogController;
use App\Http\Controllers\Storefront\HomeController;
use App\Http\Controllers\Storefront\SearchController;
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

/* Must stay last: flat category/product URLs like /spices or /radhuni-holud-gura-200g. */
Route::get('/{slug}', [CatalogController::class, 'show'])
    ->where('slug', '[a-z0-9]+(?:-[a-z0-9]+)*')
    ->name('catalog.show');
