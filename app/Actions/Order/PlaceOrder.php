<?php

namespace App\Actions\Order;

use App\Enums\OrderEventType;
use App\Enums\OrderItemStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Area;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Customer;
use App\Models\Order;
use App\Services\Pricing\OrderTotalsCalculator;
use App\Settings\StoreSettings;
use App\Support\BanglaNumber;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Turn the customer's bag into a pending COD order (docs/03-order-flow.md "PlaceOrder").
 * No stock is reserved — the business buys items after confirmation (middleman model).
 */
final class PlaceOrder
{
    public function __construct(
        private StoreSettings $settings,
        private OrderTotalsCalculator $totals,
    ) {}

    /**
     * @param  array{recipient_name: string, phone: string, area_id: int|null, area_other: string|null, address_line: string}  $address
     *
     * @throws ValidationException
     */
    public function handle(Customer $customer, Cart $cart, array $address, ?string $customerNote = null, ?string $ipAddress = null): Order
    {
        $this->guardCustomer($customer);

        return DB::transaction(function () use ($customer, $cart, $address, $customerNote, $ipAddress): Order {
            $items = $cart->items()->with('product')->orderBy('id')->get();

            $this->guardItems($items);

            $area = $address['area_id'] !== null ? Area::query()->find($address['area_id']) : null;

            $order = Order::query()->create([
                'customer_id' => $customer->id,
                'status' => OrderStatus::Pending,
                'payment_status' => PaymentStatus::Unpaid,
                'delivery_charge' => $this->settings->defaultDeliveryCharge(),
                'shipping_name' => $address['recipient_name'],
                'shipping_phone' => $address['phone'],
                'area_id' => $area?->id,
                'area_name' => $area->name ?? (string) $address['area_other'],
                'address_line' => $address['address_line'],
                'customer_note' => $customerNote,
                'placed_at' => now(),
                'ip_address' => $ipAddress,
            ]);

            $order->update(['order_number' => sprintf('ORD-%s-%04d', $order->placed_at->format('ymd'), $order->id)]);

            foreach ($items as $item) {
                $product = $item->product;

                $order->items()->create([
                    'product_id' => $product->id,
                    'category_id' => $product->category_id,
                    'product_name' => $product->name,
                    'unit_label' => $product->unit_label,
                    'sale_type' => $product->sale_type,
                    'nominal_weight_grams' => $product->nominal_weight_grams,
                    'unit_price' => $product->price,
                    'quantity' => $item->quantity,
                    'line_total' => 0,
                    'estimated_unit_cost' => $product->estimated_cost,
                    'line_cost' => 0,
                    'status' => OrderItemStatus::Pending,
                ]);
            }

            $this->totals->recalculate($order);

            $order->events()->create([
                'type' => OrderEventType::Placed,
                'to' => ['status' => OrderStatus::Pending->value],
            ]);

            $customer->increment('orders_count', 1, ['last_order_at' => now()]);
            $cart->items()->delete();

            return $order;
        });
    }

    /**
     * @throws ValidationException
     */
    private function guardCustomer(Customer $customer): void
    {
        if ($customer->is_blocked) {
            throw ValidationException::withMessages([
                'order' => 'দুঃখিত, এই অ্যাকাউন্ট থেকে এখন অর্ডার করা যাচ্ছে না। হেল্পলাইনে যোগাযোগ করুন।',
            ]);
        }

        $pendingOrders = $customer->orders()->where('status', OrderStatus::Pending)->count();

        if ($pendingOrders >= $this->settings->maxPendingOrdersPerCustomer()) {
            throw ValidationException::withMessages([
                'order' => 'আপনার আগের অর্ডার এখনো নিশ্চিত হয়নি। সেটি নিশ্চিত হওয়ার পর নতুন অর্ডার করুন।',
            ]);
        }
    }

    /**
     * @param  iterable<CartItem>  $items
     *
     * @throws ValidationException
     */
    private function guardItems(iterable $items): void
    {
        $unavailable = [];
        $count = 0;

        foreach ($items as $item) {
            $count++;

            if (! $item->product->canBeOrdered()) {
                $unavailable[] = $item->product->name;

                continue;
            }

            $limit = $item->product->maxQuantityPerOrder($this->settings);

            if ($limit !== null && $item->quantity > $limit) {
                throw ValidationException::withMessages([
                    'order' => "\"{$item->product->name}\" এক অর্ডারে সর্বোচ্চ ".BanglaNumber::digits($limit).'টি নেওয়া যাবে।',
                ]);
            }
        }

        if ($count === 0) {
            throw ValidationException::withMessages(['order' => 'আপনার ব্যাগ খালি।']);
        }

        if ($unavailable !== []) {
            throw ValidationException::withMessages([
                'order' => 'এই পণ্যগুলো এখন পাওয়া যাচ্ছে না, ব্যাগ থেকে সরিয়ে আবার চেষ্টা করুন: '.implode(', ', $unavailable),
            ]);
        }
    }
}
