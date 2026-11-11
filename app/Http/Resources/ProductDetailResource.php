<?php

namespace App\Http\Resources;

use App\Models\Product;
use App\Models\ProductImage;
use App\Support\Media;
use Illuminate\Http\Request;

/**
 * Product card data plus description and gallery, for the product page and quick view.
 * Expects the `images` relation to be eager loaded.
 *
 * @mixin Product
 */
class ProductDetailResource extends ProductCardResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $gallery = $this->images
            ->map(fn (ProductImage $image): ?string => Media::url($image->path))
            ->filter()
            ->values()
            ->all();

        return [
            ...parent::toArray($request),
            'description' => $this->description,
            'images' => array_values(array_filter([Media::url($this->image), ...$gallery])),
        ];
    }
}
