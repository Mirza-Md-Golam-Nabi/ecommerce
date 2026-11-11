<?php

namespace App\Actions\Cart;

use App\Models\Cart;
use App\Models\Customer;
use App\Services\Cart\CurrentBag;
use Illuminate\Support\Facades\DB;

/**
 * On login/registration, move the guest bag (cookie token) into the customer's bag.
 * When both bags hold the same product, the larger quantity wins (no accidental doubling).
 */
final class MergeGuestBag
{
    public function handle(Customer $customer, mixed $guestToken): void
    {
        $guestBag = CurrentBag::findGuestBag($guestToken);

        if ($guestBag === null) {
            return;
        }

        DB::transaction(function () use ($customer, $guestBag): void {
            $customerBag = Cart::query()->where('customer_id', $customer->id)->first();

            if ($customerBag === null) {
                $guestBag->update(['customer_id' => $customer->id]);

                return;
            }

            $existing = $customerBag->items()->pluck('quantity', 'product_id');

            foreach ($guestBag->items as $item) {
                $customerBag->items()->updateOrCreate(
                    ['product_id' => $item->product_id],
                    ['quantity' => max($item->quantity, (int) ($existing[$item->product_id] ?? 0))],
                );
            }

            $guestBag->delete();
            $customerBag->touch();
        });
    }
}
