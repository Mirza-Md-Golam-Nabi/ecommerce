<?php

namespace App\Filament\Resources\Orders\Tables;

use App\Enums\CustomerRisk;
use App\Models\Area;
use App\Models\Order;
use App\Support\Money;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class OrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with('customer'))
            ->defaultSort('placed_at', 'desc')
            ->columns([
                TextColumn::make('order_number')
                    ->label('অর্ডার')
                    ->description(fn (Order $record): string => $record->placed_at->format('d M, h:i A'))
                    ->searchable()
                    ->weight('bold'),
                TextColumn::make('shipping_name')
                    ->label('কাস্টমার')
                    ->description(fn (Order $record): string => $record->shipping_phone)
                    ->searchable(['shipping_name', 'shipping_phone']),
                TextColumn::make('risk')
                    ->label('ঝুঁকি')
                    ->state(fn (Order $record): CustomerRisk => CustomerRisk::for($record->customer))
                    ->badge(),
                TextColumn::make('area_name')
                    ->label('এলাকা')
                    ->toggleable(),
                TextColumn::make('items_count')
                    ->label('পণ্য'),
                TextColumn::make('grand_total')
                    ->label('মোট')
                    ->formatStateUsing(fn (int $state): string => Money::format($state))
                    ->sortable(),
                TextColumn::make('status')
                    ->label('স্ট্যাটাস')
                    ->badge(),
                TextColumn::make('placed_at')
                    ->label('সময়')
                    ->dateTime('d M Y, h:i A')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('area_id')
                    ->label('এলাকা')
                    ->options(fn (): array => Area::query()->orderBy('name')->pluck('name', 'id')->all()),
                Filter::make('placed_at')
                    ->label('তারিখ')
                    ->schema([
                        DatePicker::make('from')->label('থেকে'),
                        DatePicker::make('until')->label('পর্যন্ত'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['from'] ?? null, fn (Builder $q, string $date): Builder => $q->whereDate('placed_at', '>=', $date))
                        ->when($data['until'] ?? null, fn (Builder $q, string $date): Builder => $q->whereDate('placed_at', '<=', $date))),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }
}
