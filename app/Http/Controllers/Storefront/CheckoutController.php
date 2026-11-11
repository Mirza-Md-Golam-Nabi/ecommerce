<?php

namespace App\Http\Controllers\Storefront;

use App\Actions\Customer\SaveDefaultAddress;
use App\Actions\Order\PlaceOrder;
use App\Http\Controllers\Controller;
use App\Http\Requests\Storefront\CheckoutRequest;
use App\Models\Area;
use App\Models\Customer;
use App\Services\Cart\CurrentBag;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class CheckoutController extends Controller
{
    public function show(Request $request, CurrentBag $bag): Response|RedirectResponse
    {
        $details = $bag->details();

        if ($details['count'] === 0) {
            return redirect()->route('home')->with('error', 'আপনার ব্যাগ খালি। আগে পণ্য যোগ করুন।');
        }

        /** @var Customer $customer */
        $customer = $request->user('customer');
        $address = $customer->defaultAddress;

        return Inertia::render('Checkout', [
            'summary' => $details,
            'areas' => Area::query()->active()->get(['id', 'name']),
            'otherArea' => CheckoutRequest::OTHER_AREA,
            'address' => [
                'recipient_name' => $address->recipient_name ?? $customer->name,
                'phone' => $address->phone ?? $customer->phone,
                'area' => $address === null ? '' : ($address->area_id !== null ? (string) $address->area_id : CheckoutRequest::OTHER_AREA),
                'area_other' => $address?->area_other ?? '',
                'address_line' => $address->address_line ?? '',
            ],
        ]);
    }

    public function store(CheckoutRequest $request, CurrentBag $bag, PlaceOrder $placeOrder, SaveDefaultAddress $saveAddress): RedirectResponse
    {
        /** @var Customer $customer */
        $customer = $request->user('customer');
        $cart = $bag->find();

        if ($cart === null) {
            return redirect()->route('home')->with('error', 'আপনার ব্যাগ খালি।');
        }

        $order = DB::transaction(function () use ($request, $customer, $cart, $placeOrder, $saveAddress) {
            $order = $placeOrder->handle($customer, $cart, $request->address(), $request->validated('customer_note'), $request->ip());
            $saveAddress->handle($customer, $request->address());

            return $order;
        });

        return redirect()
            ->route('orders.show', $order)
            ->with('success', 'ধন্যবাদ! আপনার অর্ডার গ্রহণ করা হয়েছে। নিশ্চিত করতে আমরা শীঘ্রই আপনাকে ফোন করব।');
    }
}
