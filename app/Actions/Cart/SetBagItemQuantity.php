<?php

namespace App\Actions\Cart;

use App\Models\Cart;
use App\Models\Product;
use App\Settings\StoreSettings;
use App\Support\BanglaNumber;
use Illuminate\Validation\ValidationException;

/**
 * Set the absolute quantity of a product in a bag (0 removes it).
 * Absolute (not +1/-1) so repeated optimistic requests from the browser are idempotent.
 */
final class SetBagItemQuantity
{
    public function __construct(private StoreSettings $settings) {}

    /**
     * @throws ValidationException
     */
    public function handle(Cart $cart, Product $product, int $quantity): void
    {
        if ($quantity <= 0) {
            $cart->items()->where('product_id', $product->id)->delete();
            $cart->touch();

            return;
        }

        if (! $product->isVisibleInStore()) {
            throw ValidationException::withMessages(['quantity' => 'দুঃখিত, এই পণ্যটি এখন আর বিক্রি হচ্ছে না।']);
        }

        if (! $product->is_available) {
            throw ValidationException::withMessages(['quantity' => 'দুঃখিত, এই পণ্যটি আজ বাজারে পাওয়া যাচ্ছে না।']);
        }

        $maxQuantity = $product->maxQuantityPerOrder($this->settings);

        if ($maxQuantity !== null && $quantity > $maxQuantity) {
            throw ValidationException::withMessages([
                'quantity' => 'এক অর্ডারে এই পণ্যটি সর্বোচ্চ '.BanglaNumber::digits($maxQuantity).'টি নেওয়া যাবে।',
            ]);
        }

        $cart->items()->updateOrCreate(['product_id' => $product->id], ['quantity' => $quantity]);
        $cart->touch();
    }
}
