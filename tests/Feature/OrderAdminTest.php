<?php

use App\Enums\CancelReason;
use App\Enums\OrderEventType;
use App\Enums\OrderItemStatus;
use App\Enums\OrderStatus;
use App\Filament\Pages\PurchaseList;
use App\Filament\Resources\Customers\Pages\ManageCustomers;
use App\Filament\Resources\Orders\Pages\ListOrders;
use App\Filament\Resources\Orders\Pages\ViewOrder;
use App\Filament\Resources\Orders\RelationManagers\EventsRelationManager;
use App\Filament\Resources\Orders\RelationManagers\ItemsRelationManager;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Services\Pricing\OrderTotalsCalculator;
use Livewire\Livewire;

beforeEach(function () {
    $this->admin = User::factory()->create();
    $this->actingAs($this->admin);
});

function adminOrder(OrderStatus $status = OrderStatus::Pending, bool $weighted = false): Order
{
    $order = Order::factory()->status($status)->create(['delivery_charge' => 6000]);
    $factory = OrderItem::factory()->for($order);
    ($weighted ? $factory->weighted() : $factory)->create(['unit_price' => 8000, 'quantity' => 2, 'estimated_unit_cost' => 6500]);

    return app(OrderTotalsCalculator::class)->recalculate($order);
}

it('lists active orders by default and finished ones in their tab', function () {
    $pending = adminOrder();
    $delivered = adminOrder(OrderStatus::Delivered);

    Livewire::test(ListOrders::class)
        ->assertOk()
        ->assertCanSeeTableRecords([$pending])
        ->assertCanNotSeeTableRecords([$delivered])
        ->set('activeTab', 'delivered')
        ->assertCanSeeTableRecords([$delivered]);
});

it('renders the order page with items and history', function () {
    $order = adminOrder();

    Livewire::test(ViewOrder::class, ['record' => $order->getRouteKey()])->assertOk();
    Livewire::test(ItemsRelationManager::class, ['ownerRecord' => $order, 'pageClass' => ViewOrder::class])
        ->assertOk()
        ->assertCanSeeTableRecords($order->items);
    Livewire::test(EventsRelationManager::class, ['ownerRecord' => $order, 'pageClass' => ViewOrder::class])->assertOk();
});

it('confirms an order and records who did it', function () {
    $order = adminOrder();

    Livewire::test(ViewOrder::class, ['record' => $order->getRouteKey()])
        ->assertActionVisible('confirm')
        ->assertActionHidden('markDelivered')
        ->callAction('confirm');

    expect($order->fresh()->status)->toBe(OrderStatus::Confirmed)
        ->and($order->events()->where('type', OrderEventType::StatusChanged)->value('user_id'))->toBe($this->admin->id);
});

it('cancels with a reason', function () {
    $order = adminOrder(OrderStatus::Confirmed);

    Livewire::test(ViewOrder::class, ['record' => $order->getRouteKey()])
        ->callAction('cancel', ['cancel_reason' => CancelReason::Unreachable->value, 'note' => 'ফোন বন্ধ']);

    expect($order->fresh()->status)->toBe(OrderStatus::Cancelled)
        ->and($order->fresh()->cancel_reason)->toBe(CancelReason::Unreachable);
});

it('blocks delivery until weighted items are weighed, then records the collected cash', function () {
    $order = adminOrder(OrderStatus::OutForDelivery, weighted: true);

    Livewire::test(ViewOrder::class, ['record' => $order->getRouteKey()])
        ->callAction('markDelivered', ['collected_amount' => 220]);

    expect($order->fresh()->status)->toBe(OrderStatus::OutForDelivery);

    Livewire::test(ItemsRelationManager::class, ['ownerRecord' => $order, 'pageClass' => ViewOrder::class])
        ->callTableAction('setWeight', $order->items->first(), ['kg' => 2.08]);

    expect($order->fresh()->subtotal)->toBe(16640);

    Livewire::test(ViewOrder::class, ['record' => $order->getRouteKey()])
        ->callAction('markDelivered', ['collected_amount' => 226.40]);

    expect($order->fresh()->status)->toBe(OrderStatus::Delivered)
        ->and($order->fresh()->collected_amount)->toBe(22640);
});

it('changes the delivery charge from taka input', function () {
    $order = adminOrder();

    Livewire::test(ViewOrder::class, ['record' => $order->getRouteKey()])
        ->callAction('deliveryCharge', ['delivery_charge' => 40]);

    expect($order->fresh()->delivery_charge)->toBe(4000);
});

it('marks an item unavailable and sets cost from the items table', function () {
    $order = adminOrder(OrderStatus::Confirmed);
    OrderItem::factory()->for($order)->create(['unit_price' => 5000, 'quantity' => 1]);
    $order = app(OrderTotalsCalculator::class)->recalculate($order);
    [$first, $second] = $order->items;

    Livewire::test(ItemsRelationManager::class, ['ownerRecord' => $order, 'pageClass' => ViewOrder::class])
        ->callTableAction('markUnavailable', $second, ['note' => 'নেই'])
        ->callTableAction('setCost', $first, ['unit_cost' => 70]);

    expect($second->fresh()->status)->toBe(OrderItemStatus::Unavailable)
        ->and($first->fresh()->actual_unit_cost)->toBe(7000);
});

it('hides item changes on finished orders', function () {
    $order = adminOrder(OrderStatus::Delivered);

    Livewire::test(ItemsRelationManager::class, ['ownerRecord' => $order, 'pageClass' => ViewOrder::class])
        ->assertTableActionHidden('markUnavailable', $order->items->first())
        ->assertTableActionHidden('setCost', $order->items->first());
});

it('prints an invoice for admins only', function () {
    $order = adminOrder();

    $this->get(route('admin.orders.invoice', $order))->assertOk()->assertSee($order->order_number);

    auth()->logout();
    $this->get(route('admin.orders.invoice', $order))->assertRedirect();
});

it('lists customers and blocks one', function () {
    $customer = Customer::factory()->create();

    Livewire::test(ManageCustomers::class)
        ->assertCanSeeTableRecords([$customer])
        ->call('updateTableColumnState', 'is_blocked', (string) $customer->getKey(), true);

    expect($customer->fresh()->is_blocked)->toBeTrue();
});

it('builds the purchase list and applies a bought price', function () {
    $product = Product::factory()->create(['name' => 'মসুর ডাল']);
    $confirmed = Order::factory()->status(OrderStatus::Confirmed)->create();
    $pending = Order::factory()->create();
    $line = OrderItem::factory()->for($confirmed)->for($product)->create(['quantity' => 3]);
    OrderItem::factory()->for($pending)->for($product)->create(['quantity' => 5]);

    Livewire::test(PurchaseList::class)
        ->assertOk()
        ->assertCanSeeTableRecords([$product])
        ->assertTableColumnStateSet('to_buy_quantity', 3, $product)
        ->callTableAction('applyCost', $product, ['unit_cost' => 120]);

    expect($line->fresh()->actual_unit_cost)->toBe(12000)
        ->and($line->fresh()->status)->toBe(OrderItemStatus::Fulfilled);

    Livewire::test(PurchaseList::class)->assertCanNotSeeTableRecords([$product]);
});

it('moves confirmed orders to purchasing from the purchase list', function () {
    $order = adminOrder(OrderStatus::Confirmed);

    Livewire::test(PurchaseList::class)->callTableAction('startPurchasing');

    expect($order->fresh()->status)->toBe(OrderStatus::Purchasing);
});
