<?php

use App\Actions\Order\ChangeOrderStatus;
use App\Enums\OrderStatus;
use App\Filament\Pages\Reports;
use App\Filament\Widgets\DashboardSalesChart;
use App\Filament\Widgets\DashboardStats;
use App\Filament\Widgets\Reports\AreaSalesTable;
use App\Filament\Widgets\Reports\CancelReasonsTable;
use App\Filament\Widgets\Reports\CategorySalesTable;
use App\Filament\Widgets\Reports\ReportStats;
use App\Filament\Widgets\Reports\SalesByPeriodTable;
use App\Filament\Widgets\Reports\SalesChart;
use App\Filament\Widgets\Reports\TopProductsTable;
use App\Filament\Widgets\Reports\UnavailableProductsTable;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Services\Pricing\OrderTotalsCalculator;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create());

    $this->product = Product::factory()->create(['name' => 'মসুর ডাল']);
    $order = Order::factory()->status(OrderStatus::OutForDelivery)->create(['area_name' => 'ধানমন্ডি']);
    OrderItem::factory()->for($order)->for($this->product)->create(['unit_price' => 14000, 'quantity' => 2]);
    app(OrderTotalsCalculator::class)->recalculate($order);
    app(ChangeOrderStatus::class)->handle($order->fresh(), OrderStatus::Delivered);
});

it('renders the admin dashboard with store widgets', function () {
    $this->get('/admin')->assertOk();

    Livewire::test(DashboardStats::class)->assertOk()->assertSee('৳280');
    Livewire::test(DashboardSalesChart::class)->assertOk();
});

it('renders the reports page and every report widget', function () {
    $this->get('/admin/reports')->assertOk();

    $filters = ['from' => now()->subDays(6)->toDateString(), 'until' => now()->toDateString(), 'group_by' => 'day'];

    Livewire::test(ReportStats::class, ['pageFilters' => $filters])->assertOk()->assertSee('৳280');
    Livewire::test(SalesChart::class, ['pageFilters' => $filters])->assertOk();

    foreach ([SalesByPeriodTable::class, CategorySalesTable::class, UnavailableProductsTable::class, AreaSalesTable::class, CancelReasonsTable::class] as $widget) {
        Livewire::test($widget, ['pageFilters' => $filters])->assertOk();
    }

    Livewire::test(TopProductsTable::class, ['pageFilters' => $filters])
        ->assertOk()
        ->assertSee('মসুর ডাল');

    Livewire::test(AreaSalesTable::class, ['pageFilters' => $filters])->assertSee('ধানমন্ডি');
});

it('hides sales outside the selected range', function () {
    $filters = ['from' => now()->subDays(20)->toDateString(), 'until' => now()->subDays(10)->toDateString()];

    Livewire::test(TopProductsTable::class, ['pageFilters' => $filters])->assertDontSee('মসুর ডাল');
});

it('shows the reports page filters', function () {
    Livewire::test(Reports::class)->assertOk()->assertSee('দেখাও');
});
