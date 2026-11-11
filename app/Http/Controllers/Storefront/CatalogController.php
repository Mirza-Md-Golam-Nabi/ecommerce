<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductCardResource;
use App\Http\Resources\ProductDetailResource;
use App\Models\Product;
use App\Services\Catalog\CategoryTree;
use App\Support\Media;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Resolves flat storefront URLs `/{slug}`: a category (sub-category tiles or a product grid)
 * or a product page. Categories and products share one slug namespace (UniqueStoreSlug).
 */
class CatalogController extends Controller
{
    public const PER_PAGE = 24;

    public const SORTS = ['name', 'price_asc', 'price_desc', 'popular'];

    public function show(Request $request, string $slug, CategoryTree $tree): Response
    {
        $category = $tree->findBySlug($slug);

        if ($category !== null && $tree->isVisible($category['id'])) {
            return $category['is_leaf']
                ? $this->leafCategory($request, $category, $tree)
                : $this->branchCategory($category, $tree);
        }

        $product = $this->findVisibleProduct($slug, $tree);

        abort_if($product === null, 404);

        return Inertia::render('Product', [
            'product' => (new ProductDetailResource($product))->resolve(),
            'breadcrumbs' => $this->breadcrumbs($product->category_id, $tree),
        ]);
    }

    /**
     * Product details as JSON for the quick-view modal on listings.
     */
    public function quickView(string $slug, CategoryTree $tree): JsonResponse
    {
        $product = $this->findVisibleProduct($slug, $tree);

        abort_if($product === null, 404);

        return response()->json(['product' => (new ProductDetailResource($product))->resolve()]);
    }

    /**
     * @param  array<string, mixed>  $category
     */
    private function branchCategory(array $category, CategoryTree $tree): Response
    {
        return Inertia::render('Category', [
            'category' => $this->categoryData($category),
            'breadcrumbs' => $this->breadcrumbs($category['id'], $tree),
            'subcategories' => array_map(fn (array $child): array => [
                ...$this->categoryData($child),
                'image' => Media::url($child['image']),
            ], $tree->children($category['id'])),
            'products' => null,
            'sort' => null,
        ]);
    }

    /**
     * @param  array<string, mixed>  $category
     */
    private function leafCategory(Request $request, array $category, CategoryTree $tree): Response
    {
        $sort = in_array($request->query('sort'), self::SORTS, true) ? $request->query('sort') : 'name';

        $query = Product::query()
            ->active()
            ->where('category_id', $category['id'])
            ->orderByDesc('is_available');

        $products = $this->applySort($query, $sort)
            ->simplePaginate(self::PER_PAGE)
            ->withQueryString()
            ->through(fn (Product $product): array => (new ProductCardResource($product))->resolve());

        return Inertia::render('Category', [
            'category' => $this->categoryData($category),
            'breadcrumbs' => $this->breadcrumbs($category['id'], $tree),
            'subcategories' => [],
            'products' => Inertia::scroll($products),
            'sort' => $sort,
        ]);
    }

    /**
     * @param  Builder<Product>  $query
     * @return Builder<Product>
     */
    public static function applySort(Builder $query, string $sort): Builder
    {
        $sorted = match ($sort) {
            'price_asc' => $query->orderBy('price')->orderBy('name'),
            'price_desc' => $query->orderByDesc('price')->orderBy('name'),
            'popular' => $query->orderByDesc('total_sold')->orderBy('name'),
            default => $query->orderBy('name'),
        };

        return $sorted->orderBy('id');
    }

    private function findVisibleProduct(string $slug, CategoryTree $tree): ?Product
    {
        $product = Product::query()->active()->with('images')->where('slug', $slug)->first();

        return $product !== null && $tree->isVisible($product->category_id) ? $product : null;
    }

    /**
     * @param  array<string, mixed>  $category
     * @return array{id: int, name: string, slug: string, icon: string|null}
     */
    private function categoryData(array $category): array
    {
        return [
            'id' => $category['id'],
            'name' => $category['name'],
            'slug' => $category['slug'],
            'icon' => $category['icon'],
        ];
    }

    /**
     * @return list<array{name: string, slug: string}>
     */
    private function breadcrumbs(int $categoryId, CategoryTree $tree): array
    {
        return array_map(
            fn (array $node): array => ['name' => $node['name'], 'slug' => $node['slug']],
            $tree->lineage($categoryId),
        );
    }
}
