<?php

namespace App\Filament\Widgets\Reports;

use App\Services\Reports\ProductReport;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Products the market often didn't have — candidates for a new supplier/shop.
 */
class UnavailableProductsTable extends ReportTableWidget
{
    protected static ?string $heading = 'বাজারে পাওয়া যায়নি এমন পণ্য';

    public function table(Table $table): Table
    {
        return $table
            ->records(fn (): array => self::keyed(app(ProductReport::class)->unavailable($this->range(), 15), 'product_id'))
            ->paginated(false)
            ->emptyStateHeading('সব পণ্যই পাওয়া গেছে 🎉')
            ->columns([
                TextColumn::make('name')->label('পণ্য')->description(fn (array $record): string => $record['unit_label']),
                TextColumn::make('unavailable_count')->label('কতবার পাওয়া যায়নি')->alignEnd(),
                self::moneyColumn('lost_value', 'হারানো বিক্রি')->color('warning'),
            ]);
    }
}
