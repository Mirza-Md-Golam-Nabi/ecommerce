<?php

namespace App\Filament\Widgets\Reports;

use App\Services\Reports\ProductReport;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CategorySalesTable extends ReportTableWidget
{
    protected static ?string $heading = 'ক্যাটাগরি অনুযায়ী বিক্রি';

    public function table(Table $table): Table
    {
        return $table
            ->records(fn (): array => self::keyed(app(ProductReport::class)->categories($this->range()), 'category_id'))
            ->paginated(false)
            ->emptyStateHeading('এই সময়ে কোনো বিক্রি নেই')
            ->columns([
                TextColumn::make('name')->label('ক্যাটাগরি')->wrap(),
                TextColumn::make('quantity_sold')->label('বিক্রি (একক)')->alignEnd(),
                self::moneyColumn('revenue', 'বিক্রি'),
                self::moneyColumn('profit', 'লাভ')->weight('bold'),
            ]);
    }
}
