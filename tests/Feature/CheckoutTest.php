<?php

use App\Enums\OrderEventType;
use App\Enums\OrderStatus;
use App\Enums\SaleType;
use App\Models\Area;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\Order;
use App\Models\Product;
use App\Settings\StoreSettings;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    app(StoreSettings::class)->save(['default_delivery_charge' => 6000]);

    $this->customer = Customer::factory()->create(['name' => 'রহিম', 'phone' => '01712345678']);
    $this->area = Area::factory()->create(['name' => 'ধানমন্ডি']);
    $this->cart = Cart::factory()->create(['customer_id' => $this->customer->id]);

    $this->dal = Product::factory()->create(['name' => 'মসুর ডাল', 'price' => 14000, 'estimated_cost' => 12500]);
    $this->onion = Product::factory()->weighted(1000)->create(['name' => 'দেশি পেঁয়াজ', 'price' => 8000, 'estimated_cost' => 6800]);

    CartItem::factory()->for($this->cart)->for($this->dal)->create(['quantity' => 2]);
    CartItem::factory()->for($this->cart)->for($this->onion)->create(['quantity' => 3]);

    $this->actingAs($this->customer, 'customer');
});

function checkoutPayload(array $overrides = []): array
{
    return [
        'recipient_name' => 'রহিম উদ্দিন',
        'phone' => '01812-345678',
        'area' => (string) test()->area->id,
        'area_other' => '',
        'address_line' => 'বাড়ি ১২, রোড ৫',
        'customer_note' => 'সন্ধ্যায় দিবেন',
        ...$overrides,
    ];
}

it('shows the checkout page with the bag summary and a prefilled address', function () {
    $this->get('/checkout')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Checkout')
            ->where('summary.subtotal', 52000)
            ->where('summary.delivery_charge', 6000)
            ->where('address.recipient_name', 'রহিম')
            ->where('address.phone', '01712345678')
            ->has('areas', 1));
});

it('redirects home when the bag is empty', function () {
    $this->cart->items()->delete();

    $this->get('/checkout')->assertRedirect('/');
});

it('places a COD order with snapshots, totals and an audit event', function () {
    $response = $this->post('/checkout', checkoutPayload());

    $order = Order::query()->with('items', 'events')->sole();

    $response->assertRedirect("/orders/{$order->order_number}");

    expect($order->order_number)->toBe(sprintf('ORD-%s-%04d', now()->format('ymd'), $order->id))
        ->and($order->status)->toBe(OrderStatus::Pending)
        ->and($order->shipping_phone)->toBe('01812345678')
        ->and($order->area_name)->toBe('ধানমন্ডি')
        ->and($order->subtotal)->toBe(52000)
        ->and($order->delivery_charge)->toBe(6000)
        ->and($order->grand_total)->toBe(58000)
        ->and($order->cost_total)->toBe(2 * 12500 + 3 * 6800)
        ->and($order->cost_is_final)->toBeFalse()
        ->and($order->items_count)->toBe(2)
        ->and($order->customer_note)->toBe('সন্ধ্যায় দিবেন')
        ->and($order->events->first()->type)->toBe(OrderEventType::Placed);

    $onionLine = $order->items->firstWhere('product_id', $this->onion->id);

    expect($onionLine->sale_type)->toBe(SaleType::Weighted)
        ->and($onionLine->nominal_weight_grams)->toBe(1000)
        ->and($onionLine->unit_price)->toBe(8000)
        ->and($onionLine->line_total)->toBe(24000);

    expect($this->cart->items()->count())->toBe(0)
        ->and($this->customer->fresh()->orders_count)->toBe(1);
});

it('keeps the order snapshot when the product changes later', function () {
    $this->post('/checkout', checkoutPayload());

    $this->dal->update(['name' => 'নতুন নাম', 'price' => 99900]);

    $line = Order::query()->sole()->items()->where('product_id', $this->dal->id)->sole();

    expect($line->product_name)->toBe('মসুর ডাল')
        ->and($line->unit_price)->toBe(14000);
});

it('saves the address as default for next time, including the other-area option', function () {
    $this->post('/checkout', checkoutPayload(['area' => 'other', 'area_other' => 'কামরাঙ্গীরচর']));

    $address = CustomerAddress::query()->sole();

    expect($address->area_id)->toBeNull()
        ->and($address->area_other)->toBe('কামরাঙ্গীরচর')
        ->and($address->is_default)->toBeTrue()
        ->and(Order::query()->sole()->area_name)->toBe('কামরাঙ্গীরচর');

    CartItem::factory()->for($this->cart)->for($this->dal)->create(['quantity' => 1]);

    $this->get('/checkout')->assertInertia(fn (Assert $page) => $page
        ->where('address.area', 'other')
        ->where('address.area_other', 'কামরাঙ্গীরচর'));
});

it('validates the address', function () {
    $this->post('/checkout', checkoutPayload(['area' => 'other', 'area_other' => '']))->assertSessionHasErrors('area_other');
    $this->post('/checkout', checkoutPayload(['phone' => '123']))->assertSessionHasErrors('phone');
    $this->post('/checkout', checkoutPayload(['address_line' => '']))->assertSessionHasErrors('address_line');

    $inactive = Area::factory()->inactive()->create();
    $this->post('/checkout', checkoutPayload(['area' => (string) $inactive->id]))->assertSessionHasErrors('area');

    expect(Order::query()->count())->toBe(0);
});

it('refuses blocked customers', function () {
    $this->customer->update(['is_blocked' => true]);

    $this->post('/checkout', checkoutPayload())->assertSessionHasErrors('order');

    expect(Order::query()->count())->toBe(0);
});

it('limits pending orders per customer', function () {
    app(StoreSettings::class)->save(['max_pending_orders_per_customer' => 1]);
    Order::factory()->for($this->customer)->create();

    $this->post('/checkout', checkoutPayload())->assertSessionHasErrors('order');
});

it('refuses products that became unavailable or exceed the limit', function () {
    $this->onion->update(['is_available' => false]);

    $this->post('/checkout', checkoutPayload())->assertSessionHasErrors('order');

    $this->onion->update(['is_available' => true]);
    $this->dal->update(['max_per_order' => 1]);

    $this->post('/checkout', checkoutPayload())->assertSessionHasErrors('order');

    expect(Order::query()->count())->toBe(0)
        ->and($this->cart->items()->count())->toBe(2);
});
