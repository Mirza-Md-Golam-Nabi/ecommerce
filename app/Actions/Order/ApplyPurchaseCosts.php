<?php

namespace App\Actions\Order;

use App\Enums\OrderItemStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Services\Pricing\OrderTotalsCalculator;
use Illuminate\Support\Facades\DB;

/**
 * From the purchase list: "I bought product X at this unit cost" — applies the cost to every line
 * still to buy (OrderItem::toBuy) and marks fixed-price lines as bought in full.
 * Weighted lines stay pending until their actual weight is entered on the order.
 */
final class ApplyPurchaseCosts
{
    public function __construct(
        private SetItemCost $setItemCost,
        private OrderTotalsCalculator $totals,
    ) {}

    /**
     * @return int Number of order lines updated.
     */
    public function handle(Product $product, int $unitCost, ?User $by = null): int
    {
        return DB::transaction(function () use ($product, $unitCost, $by): int {
            $items = OrderItem::query()->toBuy()->where('product_id', $product->id)->with('order')->get();

            foreach ($items as $item) {
                $this->setItemCost->handle($item, $unitCost, $by, recalculate: false);

                if (! $item->isWeighted()) {
                    $item->update(['status' => OrderItemStatus::Fulfilled, 'fulfilled_quantity' => $item->quantity]);
                }
            }

            $items->pluck('order')->unique('id')->each(fn (Order $order): Order => $this->totals->recalculate($order));

            return $items->count();
        });
    }
}
