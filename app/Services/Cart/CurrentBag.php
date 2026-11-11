<?php

namespace App\Services\Cart;

use App\Http\Resources\ProductCardResource;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Customer;
use App\Settings\StoreSettings;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;

/**
 * Resolves the current visitor's bag: the logged-in customer's bag, otherwise the guest bag
 * from the `bag_token` cookie. Guest bags are merged into the customer bag on login (MergeGuestBag).
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
        $customer = Auth::guard('customer')->user();

        if ($customer instanceof Customer) {
            return Cart::query()->where('customer_id', $customer->id)->first();
        }

        return self::findGuestBag(request()->cookie(self::COOKIE));
    }

    /**
     * Find the bag or start a new one. Guests get the cookie (re)queued so it stays alive for 30 days.
     */
    public function findOrCreate(): Cart
    {
        $customer = Auth::guard('customer')->user();

        if ($customer instanceof Customer) {
            return Cart::query()->firstOrCreate(['customer_id' => $customer->id], ['token' => (string) Str::uuid()]);
        }

        $cart = $this->find() ?? Cart::query()->create(['token' => (string) Str::uuid()]);

        Cookie::queue(self::COOKIE, $cart->token, self::COOKIE_MINUTES);

        return $cart;
    }

    /**
     * A guest bag by its cookie token (never a bag that already belongs to a customer).
     */
    public static function findGuestBag(mixed $token): ?Cart
    {
        if (! is_string($token) || ! Str::isUuid($token)) {
            return null;
        }

        return Cart::query()->whereNull('customer_id')->where('token', $token)->first();
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
