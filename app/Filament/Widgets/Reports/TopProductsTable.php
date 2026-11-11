<?php

namespace App\Filament\Widgets\Reports;

use App\Services\Reports\ProductReport;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TopProductsTable extends ReportTableWidget
{
    protected static ?string $heading = 'সবচেয়ে বেশি বিক্রি হওয়া পণ্য';

    public function table(Table $table): Table
    {
        return $table
            ->records(fn (): array => self::keyed(app(ProductReport::class)->top($this->range(), 'revenue', 15), 'product_id'))
            ->paginated(false)
            ->emptyStateHeading('এই সময়ে কোনো বিক্রি নেই')
            ->columns([
                TextColumn::make('name')->label('পণ্য')->description(fn (array $record): string => $record['unit_label']),
                TextColumn::make('quantity_sold')->label('বিক্রি (একক)')->alignEnd(),
                self::moneyColumn('revenue', 'বিক্রি'),
                self::moneyColumn('profit', 'লাভ')->weight('bold'),
            ]);
    }
}
