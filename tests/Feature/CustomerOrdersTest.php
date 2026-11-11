<?php

use App\Actions\Order\ChangeOrderStatus;
use App\Enums\CancelReason;
use App\Enums\OrderItemStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Services\Pricing\OrderTotalsCalculator;
use Illuminate\Validation\ValidationException;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->withoutVite();
    $this->customer = Customer::factory()->create();
    $this->actingAs($this->customer, 'customer');
});

it('lists only the customer own orders', function () {
    Order::factory()->for($this->customer)->count(2)->create();
    Order::factory()->create();

    $this->get('/orders')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('Orders/Index')->has('orders.data', 2));
});

it('hides other customers orders', function () {
    $other = Order::factory()->create();

    $this->get("/orders/{$other->order_number}")->assertNotFound();
});

it('shows the polite message for items that could not be delivered', function () {
    $order = Order::factory()->for($this->customer)->create();
    OrderItem::factory()->for($order)->create(['product_name' => 'দেশি পেঁয়াজ', 'status' => OrderItemStatus::Unavailable]);
    app(OrderTotalsCalculator::class)->recalculate($order);

    $this->get("/orders/{$order->order_number}")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Orders/Show')
            ->where('order.items.0.line_total', 0)
            ->where('order.items.0.message', fn (string $message) => str_contains($message, 'দেশি পেঁয়াজ'))
            ->where('order.can_cancel', true));
});

it('lets the customer cancel a pending order only', function () {
    $pending = Order::factory()->for($this->customer)->create();
    $confirmed = Order::factory()->for($this->customer)->status(OrderStatus::Confirmed)->create();

    $this->post("/orders/{$pending->order_number}/cancel")->assertSessionHas('success');
    $this->post("/orders/{$confirmed->order_number}/cancel")->assertSessionHas('error');

    expect($pending->fresh()->status)->toBe(OrderStatus::Cancelled)
        ->and($pending->fresh()->cancel_reason)->toBe(CancelReason::CustomerRequest)
        ->and($confirmed->fresh()->status)->toBe(OrderStatus::Confirmed)
        ->and($this->customer->fresh()->cancelled_count)->toBe(1);
});

it('rejects invalid status transitions', function () {
    $order = Order::factory()->create();

    app(ChangeOrderStatus::class)->handle($order, OrderStatus::Delivered);
})->throws(ValidationException::class);

it('requires a cancel reason', function () {
    $order = Order::factory()->create();

    app(ChangeOrderStatus::class)->handle($order, OrderStatus::Cancelled);
})->throws(ValidationException::class);

it('requires actual weights before delivery and then marks the order paid', function () {
    $product = Product::factory()->create(['total_sold' => 0]);
    $order = Order::factory()->status(OrderStatus::OutForDelivery)->create();
    $item = OrderItem::factory()->for($order)->for($product)->weighted()->create(['quantity' => 2, 'unit_price' => 8000]);
    app(OrderTotalsCalculator::class)->recalculate($order);

    expect(fn () => app(ChangeOrderStatus::class)->handle($order, OrderStatus::Delivered))->toThrow(ValidationException::class);

    $item->update(['actual_weight_grams' => 2080]);
    app(OrderTotalsCalculator::class)->recalculate($order);
    app(ChangeOrderStatus::class)->handle($order->fresh(), OrderStatus::Delivered);

    $order->refresh();

    expect($order->status)->toBe(OrderStatus::Delivered)
        ->and($order->delivered_at)->not->toBeNull()
        ->and($order->payment_status)->toBe(PaymentStatus::Paid)
        ->and($order->subtotal)->toBe(16640)
        ->and($order->collected_amount)->toBe($order->grand_total)
        ->and($order->customer->delivered_count)->toBe(1)
        ->and($product->fresh()->total_sold)->toBe(2);
});
