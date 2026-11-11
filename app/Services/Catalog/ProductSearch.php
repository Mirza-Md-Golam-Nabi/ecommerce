<?php

namespace App\Services\Catalog;

use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Storefront product search over `name` (Bangla) and `search_keywords` (English/Banglish).
 * MySQL uses the ngram FULLTEXT index; other drivers (SQLite in tests) and 1-character
 * terms fall back to LIKE, which is fine for ~1,000 products.
 */
final class ProductSearch
{
    public function __construct(private CategoryTree $tree) {}

    /**
     * @return Builder<Product>
     */
    public function query(string $term): Builder
    {
        $term = trim(mb_substr($term, 0, 100));

        $query = Product::query()
            ->active()
            ->whereIn('category_id', $this->tree->visibleIds());

        if (DB::getDriverName() === 'mysql' && mb_strlen($term) >= 2) {
            return $query->whereFullText(['name', 'search_keywords'], $term);
        }

        $like = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $term).'%';

        return $query->where(fn (Builder $inner): Builder => $inner
            ->where('name', 'like', $like)
            ->orWhere('search_keywords', 'like', $like));
    }
}
