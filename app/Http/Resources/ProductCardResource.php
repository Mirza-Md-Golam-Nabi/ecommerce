<?php

namespace App\Http\Resources;

use App\Models\Product;
use App\Services\Catalog\CategoryTree;
use App\Settings\StoreSettings;
use App\Support\Media;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Product data needed by a storefront product card (listing, search, bag).
 *
 * @mixin Product
 */
class ProductCardResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array{
     *     id: int,
     *     name: string,
     *     slug: string,
     *     unit_label: string,
     *     price: int,
     *     compare_at_price: int|null,
     *     discount_percent: int|null,
     *     image: string|null,
     *     placeholder_icon: string|null,
     *     is_available: bool,
     *     is_weighted: bool,
     *     max_quantity: int|null,
     * }
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'unit_label' => $this->unit_label,
            'price' => $this->price,
            'compare_at_price' => $this->discountPercent() !== null ? $this->compare_at_price : null,
            'discount_percent' => $this->discountPercent(),
            'image' => Media::url($this->image),
            'placeholder_icon' => app(CategoryTree::class)->find($this->category_id)['icon'] ?? null,
            'is_available' => $this->is_available,
            'is_weighted' => $this->isWeighted(),
            'max_quantity' => $this->maxQuantityPerOrder(app(StoreSettings::class)),
        ];
    }
}
