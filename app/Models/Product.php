<?php

namespace App\Models;

use App\Enums\SaleType;
use App\Services\Catalog\CategoryTree;
use App\Settings\StoreSettings;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use LogicException;

/**
 * A sellable grocery item. Each pack size is its own product (no variants).
 * Money columns are poisha; weights are grams. See docs/02-database.md.
 *
 * @property int $id
 * @property int $category_id
 * @property string $name
 * @property string $slug
 * @property string|null $image
 * @property SaleType $sale_type
 * @property string $unit_label
 * @property int|null $nominal_weight_grams
 * @property int $price
 * @property int|null $compare_at_price
 * @property int $estimated_cost
 * @property int|null $max_per_order
 * @property bool $is_available
 * @property bool $is_active
 */
#[Fillable([
    'category_id', 'name', 'slug', 'search_keywords', 'description', 'image',
    'sale_type', 'unit_label', 'nominal_weight_grams',
    'price', 'compare_at_price', 'estimated_cost', 'max_per_order',
    'is_available', 'is_active',
])]
class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory, SoftDeletes;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'category_id' => 'integer',
            'sale_type' => SaleType::class,
            'nominal_weight_grams' => 'integer',
            'price' => 'integer',
            'compare_at_price' => 'integer',
            'estimated_cost' => 'integer',
            'max_per_order' => 'integer',
            'is_available' => 'boolean',
            'is_active' => 'boolean',
            'total_sold' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Product $product): void {
            if ($product->isDirty('category_id') && Category::query()->where('parent_id', $product->category_id)->exists()) {
                throw new LogicException('পণ্য শুধু শেষ ধাপের (সাব-ক্যাটাগরি নেই এমন) ক্যাটাগরিতে রাখা যায়।');
            }

            if ($product->sale_type === SaleType::Weighted && ! $product->nominal_weight_grams) {
                throw new LogicException('ওজনের পণ্যে প্রতি এককের ওজন (গ্রাম) দিতে হবে।');
            }

            if ($product->sale_type === SaleType::Fixed) {
                $product->nominal_weight_grams = null;
            }
        });
    }

    /**
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * @return HasMany<ProductImage, $this>
     */
    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order');
    }

    /**
     * @param  Builder<Product>  $query
     */
    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /**
     * Shown in the storefront: active, not deleted, and its category (with all ancestors) is active.
     */
    public function isVisibleInStore(): bool
    {
        return $this->is_active && ! $this->trashed() && app(CategoryTree::class)->isVisible($this->category_id);
    }

    /**
     * Visible and currently available in the market, so it can be added to the bag.
     */
    public function canBeOrdered(): bool
    {
        return $this->is_available && $this->isVisibleInStore();
    }

    public function isWeighted(): bool
    {
        return $this->sale_type === SaleType::Weighted;
    }

    /**
     * Discount percentage against compare_at_price, e.g. 12 for "-12%".
     */
    public function discountPercent(): ?int
    {
        if (! $this->compare_at_price || $this->compare_at_price <= $this->price) {
            return null;
        }

        return (int) round(($this->compare_at_price - $this->price) * 100 / $this->compare_at_price);
    }

    /**
     * Max units of this product per order: own limit, else store default, else null (unlimited).
     */
    public function maxQuantityPerOrder(StoreSettings $settings): ?int
    {
        return $this->max_per_order ?? $settings->defaultMaxQtyPerProduct();
    }
}
