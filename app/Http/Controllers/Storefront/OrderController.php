<?php

namespace App\Http\Controllers\Storefront;

use App\Actions\Order\ChangeOrderStatus;
use App\Enums\CancelReason;
use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\CustomerOrderResource;
use App\Models\Customer;
use App\Models\Order;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * "আমার অর্ডার": the logged-in customer's own orders only.
 */
class OrderController extends Controller
{
    public function index(Request $request): Response
    {
        /** @var Customer $customer */
        $customer = $request->user('customer');

        $orders = $customer->orders()
            ->latest('placed_at')
            ->latest('id')
            ->simplePaginate(10)
            ->through(fn (Order $order): array => [
                'order_number' => $order->order_number,
                'status' => ['value' => $order->status->value, 'label' => $order->status->getLabel()],
                'placed_at' => $order->placed_at->toIso8601String(),
                'items_count' => $order->items_count,
                'grand_total' => $order->grand_total,
            ]);

        return Inertia::render('Orders/Index', [
            'orders' => Inertia::scroll($orders),
        ]);
    }

    public function show(Request $request, Order $order): Response
    {
        $this->ensureOwnOrder($request, $order);

        return Inertia::render('Orders/Show', [
            'order' => (new CustomerOrderResource($order->load('items')))->resolve(),
        ]);
    }

    public function cancel(Request $request, Order $order, ChangeOrderStatus $changeStatus): RedirectResponse
    {
        $this->ensureOwnOrder($request, $order);

        if (! $order->status->isCancellableByCustomer()) {
            return back()->with('error', 'অর্ডারটি নিশ্চিত হয়ে গেছে, এখন বাতিল করতে হেল্পলাইনে ফোন করুন।');
        }

        $changeStatus->handle($order, OrderStatus::Cancelled, note: 'কাস্টমার নিজে বাতিল করেছেন', cancelReason: CancelReason::CustomerRequest);

        return back()->with('success', 'অর্ডারটি বাতিল করা হয়েছে।');
    }

    private function ensureOwnOrder(Request $request, Order $order): void
    {
        abort_unless($order->customer_id === $request->user('customer')?->id, 404);
    }
}
