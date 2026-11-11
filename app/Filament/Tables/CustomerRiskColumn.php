<?php

namespace App\Filament\Tables;

use App\Enums\CustomerRisk;
use App\Models\Customer;
use Closure;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Model;

/**
 * COD risk badge column. `$customer` maps the row to its Customer (default: the row itself).
 */
final class CustomerRiskColumn
{
    /**
     * @param  (Closure(Model): Customer)|null  $customer
     */
    public static function make(?Closure $customer = null): TextColumn
    {
        return TextColumn::make('risk')
            ->label('ঝুঁকি')
            ->state(fn (Model $record): CustomerRisk => CustomerRisk::for($customer ? $customer($record) : $record))
            ->badge();
    }
}
