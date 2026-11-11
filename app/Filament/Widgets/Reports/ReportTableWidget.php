<?php

namespace App\Filament\Widgets\Reports;

use App\Filament\Tables\MoneyColumn;
use App\Services\Reports\DateRange;
use Filament\Tables\Columns\TextColumn;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\TableWidget;

/**
 * Base for report tables on the Reports page: rows come from App\Services\Reports (arrays),
 * filtered by the page's date range. Not shown on the main dashboard.
 */
abstract class ReportTableWidget extends TableWidget
{
    use InteractsWithPageFilters;

    protected static bool $isDiscovered = false;

    protected function range(): DateRange
    {
        return DateRange::fromFilters($this->pageFilters);
    }

    protected static function moneyColumn(string $name, string $label): TextColumn
    {
        return MoneyColumn::make($name)->label($label)->alignEnd();
    }

    /**
     * Key rows for Filament (array records need unique keys).
     *
     * @param  list<array<string, mixed>>  $rows
     * @return array<string, array<string, mixed>>
     */
    protected static function keyed(array $rows, string $key): array
    {
        return collect($rows)->keyBy(fn (array $row): string => (string) $row[$key])->all();
    }
}
