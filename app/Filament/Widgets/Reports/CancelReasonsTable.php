<?php

namespace App\Filament\Widgets\Reports;

use App\Services\Reports\OrderReport;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CancelReasonsTable extends ReportTableWidget
{
    protected static ?string $heading = 'বাতিলের কারণ';

    public function table(Table $table): Table
    {
        return $table
            ->records(fn (): array => self::keyed(app(OrderReport::class)->cancelReasons($this->range()), 'reason'))
            ->paginated(false)
            ->emptyStateHeading('এই সময়ে কোনো অর্ডার বাতিল হয়নি')
            ->columns([
                TextColumn::make('label')->label('কারণ'),
                TextColumn::make('orders')->label('অর্ডার')->alignEnd()->weight('bold'),
            ]);
    }
}
