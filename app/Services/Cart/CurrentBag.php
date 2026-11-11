<?php

namespace App\Services\Cart;

use App\Http\Resources\ProductCardResource;
use App\Models\Cart;
use App\Models\CartItem;
use App\Settings\StoreSettings;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;

/**
 * Resolves the current visitor's bag from the `bag_token` cookie (guests).
 * Phase 3 adds customer bags and merging on login.
 *
 * @phpstan-type BagSummary array{count: int, subtotal: int, items: array<int, int>}
 */
final class CurrentBag
{
    public const COOKIE = 'bag_token';

    public const COOKIE_MINUTES = 60 * 24 * 30;

    public function __construct(private StoreSettings $settings) {}

    public function find(): ?Cart
    {
        $token = request()->cookie(self::COOKIE);

        if (! is_string($token) || ! Str::isUuid($token)) {
            return null;
        }

        return Cart::query()->where('token', $token)->first();
    }

    /**
     * Find the bag or start a new one, and (re)queue the cookie so it stays alive for 30 days.
     */
    public function findOrCreate(): Cart
    {
        $cart = $this->find() ?? Cart::query()->create(['token' => (string) Str::uuid()]);

        Cookie::queue(self::COOKIE, $cart->token, self::COOKIE_MINUTES);

        return $cart;
    }

    /**
     * Light summary shared with every page: unit count, subtotal of orderable items, and quantities by product id.
     *
     * @return BagSummary
     */
    public function summary(?Cart $cart = null): array
    {
        return $this->summarize($this->items($cart ?? $this->find()));
    }

    /**
     * @param  Collection<int, CartItem>  $items
     * @return BagSummary
     */
    private function summarize(Collection $items): array
    {
        $summary = ['count' => 0, 'subtotal' => 0, 'items' => []];

        foreach ($items as $item) {
            $summary['items'][$item->product_id] = $item->quantity;
            $summary['count'] += $item->quantity;

            if ($item->product->canBeOrdered()) {
                $summary['subtotal'] += $item->product->price * $item->quantity;
            }
        }

        return $summary;
    }

    /**
     * Full bag contents for the bag panel.
     *
     * @return array{
     *     items: list<array{product: array<string, mixed>, quantity: int, line_total: int, can_be_ordered: bool}>,
     *     count: int,
     *     subtotal: int,
     *     delivery_charge: int,
     *     total: int,
     *     has_weighted: bool,
     * }
     */
    public function details(?Cart $cart = null): array
    {
        $items = $this->items($cart ?? $this->find());
        $summary = $this->summarize($items);

        $lines = $items->map(function (CartItem $item): array {
            $canBeOrdered = $item->product->canBeOrdered();

            return [
                'product' => (new ProductCardResource($item->product))->resolve(),
                'quantity' => $item->quantity,
                'line_total' => $canBeOrdered ? $item->product->price * $item->quantity : 0,
                'can_be_ordered' => $canBeOrdered,
            ];
        })->values()->all();

        $deliveryCharge = $summary['subtotal'] > 0 ? $this->settings->defaultDeliveryCharge() : 0;

        return [
            'items' => $lines,
            'count' => $summary['count'],
            'subtotal' => $summary['subtotal'],
            'delivery_charge' => $deliveryCharge,
            'total' => $summary['subtotal'] + $deliveryCharge,
            'has_weighted' => $items->contains(fn (CartItem $item): bool => $item->product->isWeighted() && $item->product->canBeOrdered()),
        ];
    }

    /**
     * @return Collection<int, CartItem>
     */
    private function items(?Cart $cart): Collection
    {
        if ($cart === null) {
            return new Collection;
        }

        return $cart->items()->with('product')->oldest('id')->get();
    }
}
