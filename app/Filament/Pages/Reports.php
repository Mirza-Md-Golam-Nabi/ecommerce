<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\Reports\AreaSalesTable;
use App\Filament\Widgets\Reports\CancelReasonsTable;
use App\Filament\Widgets\Reports\CategorySalesTable;
use App\Filament\Widgets\Reports\ReportStats;
use App\Filament\Widgets\Reports\SalesByPeriodTable;
use App\Filament\Widgets\Reports\SalesChart;
use App\Filament\Widgets\Reports\TopProductsTable;
use App\Filament\Widgets\Reports\UnavailableProductsTable;
use BackedEnum;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Pages\Dashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

/**
 * Report page (docs/04-reporting.md): date range + grouping filters drive every widget.
 * All numbers come from the daily summary tables.
 */
class Reports extends Dashboard
{
    use HasFiltersForm;

    protected static string $routePath = 'reports';

    protected static ?string $title = 'রিপোর্ট';

    protected static ?string $navigationLabel = 'বিক্রি ও লাভ';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static string|UnitEnum|null $navigationGroup = 'রিপোর্ট';

    protected static ?int $navigationSort = 1;

    public function filtersForm(Schema $schema): Schema
    {
        return $schema->components([
            DatePicker::make('from')
                ->label('থেকে')
                ->default(now()->subDays(29)->toDateString())
                ->maxDate(now()),
            DatePicker::make('until')
                ->label('পর্যন্ত')
                ->default(now()->toDateString())
                ->maxDate(now()),
            Select::make('group_by')
                ->label('দেখাও')
                ->options(['day' => 'দিন অনুযায়ী', 'week' => 'সপ্তাহ অনুযায়ী', 'month' => 'মাস অনুযায়ী'])
                ->default('day')
                ->selectablePlaceholder(false),
        ]);
    }

    public function getWidgets(): array
    {
        return [
            ReportStats::class,
            SalesChart::class,
            SalesByPeriodTable::class,
            TopProductsTable::class,
            UnavailableProductsTable::class,
            CategorySalesTable::class,
            AreaSalesTable::class,
            CancelReasonsTable::class,
        ];
    }

    public function getColumns(): int|array
    {
        return ['md' => 2];
    }
}
