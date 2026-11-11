<?php

namespace App\Models;

use App\Enums\OrderItemStatus;
use App\Enums\OrderStatus;
use App\Enums\SaleType;
use App\Support\BanglaNumber;
use Database\Factories\OrderItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Snapshot of a product in an order. Never deleted — an item that could not be bought is marked
 * `unavailable` (docs/03-order-flow.md). `line_total`/`line_cost` come from LinePriceCalculator.
 *
 * @property int $id
 * @property int $order_id
 * @property int $product_id
 * @property int $category_id
 * @property string $product_name
 * @property string $unit_label
 * @property SaleType $sale_type
 * @property int|null $nominal_weight_grams
 * @property int $unit_price
 * @property int $quantity
 * @property int|null $fulfilled_quantity
 * @property int|null $actual_weight_grams
 * @property int $line_total
 * @property int $estimated_unit_cost
 * @property int|null $actual_unit_cost
 * @property int $line_cost
 * @property OrderItemStatus $status
 * @property string|null $unavailable_note
 */
#[Fillable([
    'order_id', 'product_id', 'category_id', 'product_name', 'unit_label', 'sale_type',
    'nominal_weight_grams', 'unit_price', 'quantity', 'fulfilled_quantity', 'actual_weight_grams',
    'line_total', 'estimated_unit_cost', 'actual_unit_cost', 'line_cost', 'status', 'unavailable_note',
])]
class OrderItem extends Model
{
    /** @use HasFactory<OrderItemFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'order_id' => 'integer',
            'product_id' => 'integer',
            'category_id' => 'integer',
            'sale_type' => SaleType::class,
            'nominal_weight_grams' => 'integer',
            'unit_price' => 'integer',
            'quantity' => 'integer',
            'fulfilled_quantity' => 'integer',
            'actual_weight_grams' => 'integer',
            'line_total' => 'integer',
            'estimated_unit_cost' => 'integer',
            'actual_unit_cost' => 'integer',
            'line_cost' => 'integer',
            'status' => OrderItemStatus::class,
        ];
    }

    /**
     * @return BelongsTo<Order, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class)->withTrashed();
    }

    public function isWeighted(): bool
    {
        return $this->sale_type === SaleType::Weighted;
    }

    /**
     * Estimated grams ordered (quantity × nominal unit weight); 0 for fixed-price items.
     */
    public function orderedGrams(): int
    {
        return $this->quantity * (int) $this->nominal_weight_grams;
    }

    /**
     * Purchase list: items of confirmed/purchasing orders not yet bought (no actual cost, still pending).
     *
     * @param  Builder<OrderItem>  $query
     */
    #[Scope]
    protected function toBuy(Builder $query): void
    {
        $query->where('status', OrderItemStatus::Pending)
            ->whereNull('actual_unit_cost')
            ->whereHas('order', fn (Builder $order): Builder => $order->whereIn('status', [OrderStatus::Confirmed, OrderStatus::Purchasing]));
    }

    /**
     * Polite Bangla message for the customer when the item was not (fully) delivered.
     */
    public function customerMessage(): ?string
    {
        return match ($this->status) {
            OrderItemStatus::Unavailable => "🌿 দুঃখিত, আজ বাজারে মানসম্মত \"{$this->product_name}\" পাওয়া যায়নি, তাই এটি পাঠানো সম্ভব হয়নি। এর মূল্য আপনার বিল থেকে বাদ দেওয়া হয়েছে।",
            OrderItemStatus::Partial => "🌿 \"{$this->product_name}\" এর ".BanglaNumber::digits($this->quantity).'টির মধ্যে '.BanglaNumber::digits((int) $this->fulfilled_quantity).'টি পাঠানো হয়েছে, বাকিটুকুর মূল্য বিল থেকে বাদ দেওয়া হয়েছে।',
            default => null,
        };
    }
}
