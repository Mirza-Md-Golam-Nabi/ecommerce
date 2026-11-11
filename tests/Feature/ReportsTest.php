<?php

use App\Actions\Order\ChangeOrderStatus;
use App\Actions\Order\MarkItemUnavailable;
use App\Actions\Order\SetItemCost;
use App\Enums\CancelReason;
use App\Enums\OrderStatus;
use App\Models\Area;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Services\Pricing\OrderTotalsCalculator;
use App\Services\Reports\DateRange;
use App\Services\Reports\OrderReport;
use App\Services\Reports\ProductReport;
use App\Services\Reports\SalesReport;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->travelTo(CarbonImmutable::parse('2026-11-10 10:00'));

    $this->category = Category::factory()->create(['name' => 'ডাল']);
    $this->dal = Product::factory()->for($this->category)->create(['name' => 'মসুর ডাল']);
    $this->oil = Product::factory()->for($this->category)->create(['name' => 'সয়াবিন তেল']);
});

/**
 * Delivered order: 2 × dal @ ৳140 (cost ৳120) + 1 × oil @ ৳180 (cost ৳170), delivery ৳60.
 */
function deliveredOrder(?Area $area = null, ?string $otherArea = null): Order
{
    $order = Order::factory()->status(OrderStatus::OutForDelivery)->create([
        'delivery_charge' => 6000,
        'area_id' => $area?->id,
        'area_name' => $area->name ?? $otherArea ?? 'ধানমন্ডি',
    ]);
    $dal = OrderItem::factory()->for($order)->for(test()->dal)->create(['unit_price' => 14000, 'quantity' => 2, 'estimated_unit_cost' => 12000]);
    $oil = OrderItem::factory()->for($order)->for(test()->oil)->create(['unit_price' => 18000, 'quantity' => 1, 'estimated_unit_cost' => 17000]);
    app(OrderTotalsCalculator::class)->recalculate($order);

    app(SetItemCost::class)->handle($dal, 12000);
    app(SetItemCost::class)->handle($oil, 17000);

    return app(ChangeOrderStatus::class)->handle($order->fresh(), OrderStatus::Delivered);
}

function todayRange(): DateRange
{
    return DateRange::lastDays(1);
}

it('keeps daily summaries up to date automatically when orders change', function () {
    deliveredOrder();

    $totals = app(SalesReport::class)->totals(todayRange());

    expect($totals['orders_placed'])->toBe(1)
        ->and($totals['orders_delivered'])->toBe(1)
        ->and($totals['revenue'])->toBe(46000)
        ->and($totals['delivery_income'])->toBe(6000)
        ->and($totals['cogs'])->toBe(41000)
        ->and($totals['gross_profit'])->toBe(5000)
        ->and($totals['estimated_cost_orders'])->toBe(0)
        ->and($totals['items_delivered'])->toBe(3)
        ->and(SalesReport::margin($totals))->toBe(10.9);
});

it('recognises revenue on the delivery day, not the order day', function () {
    $order = Order::factory()->status(OrderStatus::OutForDelivery)->create(['placed_at' => now()->subDay()]);
    OrderItem::factory()->for($order)->create(['unit_price' => 10000, 'quantity' => 1]);
    app(OrderTotalsCalculator::class)->recalculate($order);
    app(ChangeOrderStatus::class)->handle($order->fresh(), OrderStatus::Delivered);

    $yesterday = new DateRange(CarbonImmutable::yesterday(), CarbonImmutable::yesterday());

    expect(app(SalesReport::class)->totals($yesterday)['orders_placed'])->toBe(1)
        ->and(app(SalesReport::class)->totals($yesterday)['revenue'])->toBe(0)
        ->and(app(SalesReport::class)->totals(todayRange())['revenue'])->toBe(10000);
});

it('counts a late-night order on the local (Dhaka) day', function () {
    $this->travelTo(CarbonImmutable::parse('2026-11-10 23:30', 'Asia/Dhaka'));
    Order::factory()->create();

    expect(app(SalesReport::class)->totals(todayRange())['orders_placed'])->toBe(1);
});

it('tracks cancellations and lost sales from unavailable items', function () {
    $order = Order::factory()->status(OrderStatus::Confirmed)->create();
    $line = OrderItem::factory()->for($order)->for($this->dal)->create(['unit_price' => 14000, 'quantity' => 2]);
    app(OrderTotalsCalculator::class)->recalculate($order);

    app(MarkItemUnavailable::class)->handle($line);

    $totals = app(SalesReport::class)->totals(todayRange());
    $unavailable = app(ProductReport::class)->unavailable(todayRange());

    expect($totals['orders_cancelled'])->toBe(1)
        ->and($totals['lost_sales'])->toBe(28000)
        ->and($totals['unavailable_lines'])->toBe(1)
        ->and($unavailable[0]['name'])->toBe('মসুর ডাল')
        ->and($unavailable[0]['unavailable_count'])->toBe(1)
        ->and($unavailable[0]['lost_value'])->toBe(28000)
        ->and(app(OrderReport::class)->cancelReasons(todayRange())[0]['reason'])->toBe(CancelReason::AllUnavailable->value);
});

it('ranks top products and categories', function () {
    deliveredOrder();

    $top = app(ProductReport::class)->top(todayRange(), 'quantity_sold');

    expect($top[0]['name'])->toBe('মসুর ডাল')
        ->and($top[0]['quantity_sold'])->toBe(2)
        ->and($top[0]['profit'])->toBe(4000)
        ->and(app(ProductReport::class)->top(todayRange(), 'revenue')[0]['name'])->toBe('মসুর ডাল')
        ->and(app(ProductReport::class)->categories(todayRange())[0])->toMatchArray(['name' => 'ডাল', 'revenue' => 46000]);
});

it('breaks revenue down by area, flagging typed "other" areas', function () {
    $dhanmondi = Area::factory()->create(['name' => 'ধানমন্ডি']);
    deliveredOrder($dhanmondi);
    deliveredOrder($dhanmondi);
    deliveredOrder(otherArea: 'কামরাঙ্গীরচর');

    $areas = app(OrderReport::class)->areas(todayRange());

    expect($areas)->toHaveCount(2)
        ->and($areas[0])->toMatchArray(['area' => 'ধানমন্ডি', 'is_other' => false, 'orders' => 2, 'revenue' => 92000])
        ->and($areas[1])->toMatchArray(['area' => 'কামরাঙ্গীরচর', 'is_other' => true]);
});

it('groups the series by day, week and month including empty periods', function () {
    deliveredOrder();
    $range = DateRange::lastDays(14);

    expect(app(SalesReport::class)->series($range, 'day'))->toHaveCount(14)
        ->and(collect(app(SalesReport::class)->series($range, 'week'))->sum('revenue'))->toBe(46000)
        ->and(app(SalesReport::class)->series($range, 'month'))->toHaveCount(2);
});

it('counts new customers', function () {
    Customer::factory()->count(2)->create();

    expect(app(SalesReport::class)->totals(todayRange())['new_customers'])->toBe(2);
});

it('rebuilds summaries from scratch with the command', function () {
    deliveredOrder();
    DB::table('daily_sales_summaries')->delete();
    DB::table('daily_product_sales')->delete();

    $this->artisan('reports:rebuild', ['--days' => 3])->assertSuccessful();

    expect(app(SalesReport::class)->totals(todayRange())['revenue'])->toBe(46000)
        ->and(DB::table('daily_product_sales')->count())->toBe(2);

    $this->artisan('reports:rebuild', ['--from' => now()->subDays(2)->toDateString()])->assertSuccessful();

    expect(DB::table('daily_sales_summaries')->count())->toBe(1);
});
