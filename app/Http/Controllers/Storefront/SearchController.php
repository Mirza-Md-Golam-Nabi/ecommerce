<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductCardResource;
use App\Models\Product;
use App\Services\Catalog\ProductSearch;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SearchController extends Controller
{
    public function __invoke(Request $request, ProductSearch $search): Response
    {
        $term = trim((string) $request->query('q', ''));

        $products = null;

        if ($term !== '') {
            $query = $search->query($term)->orderByDesc('is_available');

            $products = CatalogController::applySort($query, 'name')
                ->simplePaginate(CatalogController::PER_PAGE)
                ->withQueryString()
                ->through(fn (Product $product): array => (new ProductCardResource($product))->resolve());
        }

        return Inertia::render('Search', [
            'query' => $term,
            'products' => $products !== null ? Inertia::scroll($products) : null,
        ]);
    }
}
