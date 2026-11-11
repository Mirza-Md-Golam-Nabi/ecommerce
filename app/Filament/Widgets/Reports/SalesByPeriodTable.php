<?php

namespace App\Filament\Widgets\Reports;

use App\Services\Reports\SalesReport;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SalesByPeriodTable extends ReportTableWidget
{
    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'সময় অনুযায়ী বিক্রি ও লাভ';

    public function table(Table $table): Table
    {
        return $table
            ->records(fn (): array => self::keyed(
                array_reverse(app(SalesReport::class)->series($this->range(), (string) ($this->pageFilters['group_by'] ?? 'day'))),
                'period',
            ))
            ->paginated([10, 25, 50])
            ->columns([
                TextColumn::make('label')->label('সময়'),
                TextColumn::make('orders_placed')->label('অর্ডার')->alignEnd(),
                TextColumn::make('orders_delivered')->label('ডেলিভারি')->alignEnd(),
                TextColumn::make('orders_cancelled')->label('বাতিল')->alignEnd(),
                self::moneyColumn('revenue', 'বিক্রি'),
                self::moneyColumn('cogs', 'ক্রয়মূল্য'),
                self::moneyColumn('gross_profit', 'লাভ')->weight('bold'),
                self::moneyColumn('lost_sales', 'হারানো বিক্রি')->color('warning'),
            ]);
    }
}
