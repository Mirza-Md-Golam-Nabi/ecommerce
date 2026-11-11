<?php

namespace App\Filament\Widgets\Reports;

use App\Services\Reports\OrderReport;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Delivered revenue per area. Frequent "অন্যান্য" entries are worth adding as real areas.
 */
class AreaSalesTable extends ReportTableWidget
{
    protected static ?string $heading = 'এলাকা অনুযায়ী বিক্রি';

    public function table(Table $table): Table
    {
        return $table
            ->records(fn (): array => self::keyed(app(OrderReport::class)->areas($this->range()), 'area'))
            ->paginated(false)
            ->emptyStateHeading('এই সময়ে কোনো ডেলিভারি নেই')
            ->columns([
                TextColumn::make('area')
                    ->label('এলাকা')
                    ->badge(fn (array $record): bool => $record['is_other'])
                    ->color(fn (array $record): ?string => $record['is_other'] ? 'warning' : null)
                    ->description(fn (array $record): ?string => $record['is_other'] ? 'অন্যান্য — কাস্টমারের লেখা' : null),
                TextColumn::make('orders')->label('অর্ডার')->alignEnd(),
                self::moneyColumn('revenue', 'বিক্রি')->weight('bold'),
            ]);
    }
}
