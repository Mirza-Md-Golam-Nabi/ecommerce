<?php

use App\Actions\Order\ApplyPurchaseCosts;
use App\Actions\Order\MarkItemUnavailable;
use App\Actions\Order\SetDeliveryCharge;
use App\Actions\Order\SetItemCost;
use App\Actions\Order\SetItemFulfillment;
use App\Enums\CancelReason;
use App\Enums\OrderEventType;
use App\Enums\OrderItemStatus;
use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Services\Pricing\OrderTotalsCalculator;
use Illuminate\Validation\ValidationException;

/**
 * Order with one fixed line (2 × ৳140) and one weighted line (2 kg × ৳80), delivery ৳60.
 */
function orderWithLines(OrderStatus $status = OrderStatus::Confirmed): Order
{
    $order = Order::factory()->status($status)->create(['delivery_charge' => 6000]);
    OrderItem::factory()->for($order)->create(['unit_price' => 14000, 'quantity' => 2, 'estimated_unit_cost' => 12000]);
    OrderItem::factory()->for($order)->weighted()->create(['unit_price' => 8000, 'quantity' => 2, 'estimated_unit_cost' => 6500]);

    return app(OrderTotalsCalculator::class)->recalculate($order);
}

beforeEach(function () {
    $this->admin = User::factory()->create();
});

it('marks an item unavailable, keeps it, and removes it from the bill', function () {
    $order = orderWithLines();
    $fixed = $order->items[0];

    app(MarkItemUnavailable::class)->handle($fixed, 'বাজারে নেই', $this->admin);

    $order->refresh();

    expect($fixed->fresh()->status)->toBe(OrderItemStatus::Unavailable)
        ->and($order->items()->count())->toBe(2)
        ->and($order->subtotal)->toBe(16000)
        ->and($order->grand_total)->toBe(22000)
        ->and($order->unavailable_total)->toBe(28000)
        ->and($order->events()->where('type', OrderEventType::ItemUnavailable)->value('user_id'))->toBe($this->admin->id);
});

it('cancels the order when no item can be delivered', function () {
    $order = orderWithLines();

    foreach ($order->items as $item) {
        app(MarkItemUnavailable::class)->handle($item);
    }

    expect($order->fresh()->status)->toBe(OrderStatus::Cancelled)
        ->and($order->fresh()->cancel_reason)->toBe(CancelReason::AllUnavailable);
});

it('sets the actual weight of a weighted item and reprices it', function () {
    $order = orderWithLines();

    app(SetItemFulfillment::class)->handle($order->items[1], 2080, $this->admin);

    $weighted = $order->items[1]->fresh();

    expect($weighted->actual_weight_grams)->toBe(2080)
        ->and($weighted->status)->toBe(OrderItemStatus::Fulfilled)
        ->and($weighted->line_total)->toBe(16640)
        ->and($order->fresh()->subtotal)->toBe(28000 + 16640);
});

it('records a partial quantity for fixed items', function () {
    $order = orderWithLines();

    app(SetItemFulfillment::class)->handle($order->items[0], 1);

    $fixed = $order->items[0]->fresh();

    expect($fixed->status)->toBe(OrderItemStatus::Partial)
        ->and($fixed->line_total)->toBe(14000)
        ->and($order->fresh()->unavailable_total)->toBe(14000);
});

it('restores an unavailable item when it is found after all', function () {
    $order = orderWithLines();
    app(MarkItemUnavailable::class)->handle($order->items[0]);

    app(SetItemFulfillment::class)->handle($order->items[0]->fresh(), 2);

    expect($order->items[0]->fresh()->status)->toBe(OrderItemStatus::Fulfilled)
        ->and($order->fresh()->subtotal)->toBe(28000 + 16000);
});

it('validates fulfillment amounts', function (int $index, int $amount) {
    $order = orderWithLines();

    app(SetItemFulfillment::class)->handle($order->items[$index], $amount);
})->with([
    'fixed: zero' => [0, 0],
    'fixed: more than ordered' => [0, 3],
    'weighted: absurd weight' => [1, 9000],
])->throws(ValidationException::class);

it('uses the actual cost for profit and knows when costs are final', function () {
    $order = orderWithLines();

    app(SetItemCost::class)->handle($order->items[0], 12500);

    expect($order->fresh()->cost_total)->toBe(2 * 12500 + 2 * 6500)
        ->and($order->fresh()->cost_is_final)->toBeFalse();

    app(SetItemCost::class)->handle($order->items[1], 7000);

    expect($order->fresh()->cost_is_final)->toBeTrue();
});

it('changes the delivery charge with an audit event', function () {
    $order = orderWithLines();

    app(SetDeliveryCharge::class)->handle($order, 4000, $this->admin, 'কাছের ঠিকানা');

    $event = $order->events()->where('type', OrderEventType::DeliveryChargeChanged)->sole();

    expect($order->fresh()->grand_total)->toBe(44000 + 4000)
        ->and($event->from)->toBe(['delivery_charge' => 6000])
        ->and($event->to)->toBe(['delivery_charge' => 4000]);
});

it('does not change delivered or cancelled orders', function () {
    $order = orderWithLines(OrderStatus::Delivered);

    app(SetDeliveryCharge::class)->handle($order, 0);
})->throws(ValidationException::class);

it('applies purchase costs to every line still to buy', function () {
    $product = Product::factory()->create();
    $confirmed = Order::factory()->status(OrderStatus::Confirmed)->create();
    $purchasing = Order::factory()->status(OrderStatus::Purchasing)->create();
    $pending = Order::factory()->create();

    $a = OrderItem::factory()->for($confirmed)->for($product)->create(['quantity' => 2]);
    $b = OrderItem::factory()->for($purchasing)->for($product)->create(['quantity' => 1]);
    $c = OrderItem::factory()->for($pending)->for($product)->create(['quantity' => 1]);

    $updated = app(ApplyPurchaseCosts::class)->handle($product, 11000, $this->admin);

    expect($updated)->toBe(2)
        ->and($a->fresh()->actual_unit_cost)->toBe(11000)
        ->and($a->fresh()->status)->toBe(OrderItemStatus::Fulfilled)
        ->and($a->fresh()->fulfilled_quantity)->toBe(2)
        ->and($b->fresh()->actual_unit_cost)->toBe(11000)
        ->and($c->fresh()->actual_unit_cost)->toBeNull()
        ->and($confirmed->fresh()->cost_total)->toBe(22000)
        ->and(OrderItem::query()->toBuy()->count())->toBe(0);
});
