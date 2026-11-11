<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductCardResource;
use App\Services\Catalog\ProductSearch;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SearchController extends Controller
{
    public function __invoke(Request $request, ProductSearch $search): Response
    {
        $term = trim((string) $request->query('q', ''));

        return Inertia::render('Search', [
            'query' => $term,
            'products' => $term !== ''
                ? Inertia::scroll(ProductCardResource::paginate($search->query($term)->forStorefront()))
                : null,
        ]);
    }
}
