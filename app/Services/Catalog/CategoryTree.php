<?php

namespace App\Services\Catalog;

use App\Models\Category;
use Illuminate\Support\Facades\Cache;

/**
 * Cached in-memory view of the whole category tree (a few hundred rows at most).
 * Never query categories per request for navigation, breadcrumbs or labels — use this.
 * The cache is flushed automatically by Category model events.
 *
 * @phpstan-type CategoryNode array{
 *     id: int,
 *     parent_id: int|null,
 *     name: string,
 *     slug: string,
 *     image: string|null,
 *     icon: string|null,
 *     depth: int,
 *     is_leaf: bool,
 *     is_active: bool,
 *     show_on_home: bool,
 *     home_sort_order: int,
 * }
 */
final class CategoryTree
{
    public const CACHE_KEY = 'category_tree';

    /** @var array<int, CategoryNode>|null */
    private ?array $nodes = null;

    /**
     * All non-deleted categories keyed by id, ordered by name (A→Z).
     *
     * @return array<int, CategoryNode>
     */
    public function nodes(): array
    {
        return $this->nodes ??= Cache::rememberForever(self::CACHE_KEY, fn (): array => Category::query()
            ->orderBy('name')
            ->get(['id', 'parent_id', 'name', 'slug', 'image', 'icon', 'depth', 'is_leaf', 'is_active', 'show_on_home', 'home_sort_order'])
            ->mapWithKeys(fn (Category $category): array => [$category->id => [
                'id' => $category->id,
                'parent_id' => $category->parent_id,
                'name' => $category->name,
                'slug' => $category->slug,
                'image' => $category->image,
                'icon' => $category->icon,
                'depth' => $category->depth,
                'is_leaf' => $category->is_leaf,
                'is_active' => $category->is_active,
                'show_on_home' => $category->show_on_home,
                'home_sort_order' => $category->home_sort_order,
            ]])
            ->all());
    }

    public function flush(): void
    {
        Cache::forget(self::CACHE_KEY);
        $this->nodes = null;
    }

    /**
     * @return CategoryNode|null
     */
    public function find(int $id): ?array
    {
        return $this->nodes()[$id] ?? null;
    }

    /**
     * The category and its ancestors, root first (for breadcrumbs).
     *
     * @return list<CategoryNode>
     */
    public function lineage(int $id): array
    {
        $lineage = [];
        $node = $this->find($id);

        while ($node !== null) {
            array_unshift($lineage, $node);
            $node = $node['parent_id'] !== null ? $this->find($node['parent_id']) : null;
        }

        return $lineage;
    }

    /**
     * Full path label, e.g. "খাবার › রান্না › মসলা".
     */
    public function pathLabel(int $id, string $separator = ' › '): string
    {
        return implode($separator, array_column($this->lineage($id), 'name'));
    }

    /**
     * Visible to customers: the category and every ancestor are active.
     */
    public function isVisible(int $id): bool
    {
        $lineage = $this->lineage($id);

        foreach ($lineage as $node) {
            if (! $node['is_active']) {
                return false;
            }
        }

        return $lineage !== [];
    }

    /**
     * Direct children of a category (or top-level categories when null).
     *
     * @return list<CategoryNode>
     */
    public function children(?int $parentId, bool $onlyVisible = true): array
    {
        return array_values(array_filter(
            $this->nodes(),
            fn (array $node): bool => $node['parent_id'] === $parentId && (! $onlyVisible || $node['is_active']),
        ));
    }

    /**
     * Leaf categories as `id => "full › path"` options, sorted by label. Used to pick a product's category.
     *
     * @return array<int, string>
     */
    public function leafOptions(): array
    {
        $options = [];

        foreach ($this->nodes() as $id => $node) {
            if ($node['is_leaf']) {
                $options[$id] = $this->pathLabel($id);
            }
        }

        asort($options, SORT_NATURAL);

        return $options;
    }

    /**
     * Categories that may become the parent of $category: not the category itself or
     * its descendants, and not holding products. `id => "full › path"`, sorted by label.
     *
     * @param  list<int>  $categoryIdsWithProducts
     * @return array<int, string>
     */
    public function parentOptions(?Category $category, array $categoryIdsWithProducts): array
    {
        $excluded = array_flip($categoryIdsWithProducts);
        $options = [];

        foreach ($this->nodes() as $id => $node) {
            if (isset($excluded[$id])) {
                continue;
            }

            if ($category !== null && $category->exists && in_array($category->id, array_column($this->lineage($id), 'id'), true)) {
                continue;
            }

            $options[$id] = $this->pathLabel($id);
        }

        asort($options, SORT_NATURAL);

        return $options;
    }
}
