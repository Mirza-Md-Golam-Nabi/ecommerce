<?php

namespace App\Http\Controllers\Storefront;

use App\Actions\Cart\SetBagItemQuantity;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\Cart\CurrentBag;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * JSON endpoints used by the storefront bag (optimistic UI in resources/js/contexts/BagContext.tsx).
 */
class BagController extends Controller
{
    public function show(CurrentBag $bag): JsonResponse
    {
        return response()->json($bag->details());
    }

    public function update(Request $request, Product $product, CurrentBag $bag, SetBagItemQuantity $setQuantity): JsonResponse
    {
        $validated = $request->validate([
            'quantity' => ['required', 'integer', 'min:0', 'max:999'],
        ]);

        $quantity = (int) $validated['quantity'];
        $cart = $quantity > 0 ? $bag->findOrCreate() : $bag->find();

        if ($cart !== null) {
            $setQuantity->handle($cart, $product, $quantity);
        }

        return response()->json(['bag' => $bag->summary($cart)]);
    }
}
