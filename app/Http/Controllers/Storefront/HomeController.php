<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Services\Catalog\CategoryTree;
use App\Support\Media;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    /**
     * Chaldal-style home: hero + "popular categories" grid only (no product lists).
     */
    public function __invoke(CategoryTree $tree): Response
    {
        return Inertia::render('Home', [
            'categories' => array_map(fn (array $node): array => [
                'id' => $node['id'],
                'name' => $node['name'],
                'slug' => $node['slug'],
                'icon' => $node['icon'],
                'image' => Media::url($node['image']),
            ], $tree->homeCategories()),
        ]);
    }
}
