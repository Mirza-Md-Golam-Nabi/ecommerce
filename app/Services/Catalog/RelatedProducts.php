<?php

namespace App\Services\Catalog;

use App\Http\Resources\ProductCardResource;
use App\Models\Product;

/**
 * "Related products" shown under a product page: other products from the same category first,
 * topped up from visible sibling categories (same parent). Available and popular ones come first.
 */
final class RelatedProducts
{
    public const LIMIT = 10;

    public function __construct(private CategoryTree $tree) {}

    /**
     * @return list<array<string, mixed>> Product card data (see ProductCardResource).
     */
    public function for(Product $product): array
    {
        return Product::query()
            ->active()
            ->whereKeyNot($product->id)
            ->whereIn('category_id', $this->candidateCategoryIds($product->category_id))
            ->orderByDesc('is_available')
            ->orderByRaw('CASE WHEN category_id = ? THEN 0 ELSE 1 END', [$product->category_id])
            ->forStorefront('popular')
            ->limit(self::LIMIT)
            ->get()
            ->map(fn (Product $related): array => (new ProductCardResource($related))->resolve())
            ->all();
    }

    /**
     * The product's own category plus its visible siblings.
     *
     * @return list<int>
     */
    private function candidateCategoryIds(int $categoryId): array
    {
        $parentId = $this->tree->find($categoryId)['parent_id'] ?? null;

        $siblingIds = $parentId === null ? [] : array_column($this->tree->children($parentId), 'id');

        return array_values(array_unique([$categoryId, ...$siblingIds]));
    }
}
