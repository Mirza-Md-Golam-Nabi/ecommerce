<?php

namespace App\Filament\Resources\Customers;

use App\Enums\CustomerRisk;
use App\Filament\Resources\Customers\Pages\ManageCustomers;
use App\Models\Customer;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use UnitEnum;

/**
 * Customer list with COD history and the block switch (blocked customers cannot place orders).
 */
class CustomerResource extends Resource
{
    protected static ?string $model = Customer::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static string|UnitEnum|null $navigationGroup = 'অর্ডার';

    protected static ?int $navigationSort = 3;

    protected static ?string $modelLabel = 'কাস্টমার';

    protected static ?string $pluralModelLabel = 'কাস্টমার';

    protected static ?string $recordTitleAttribute = 'name';

    public static function canCreate(): bool
    {
        return false;
    }

    /**
     * @return array<string>
     */
    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'phone'];
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('name')
                    ->label('নাম')
                    ->description(fn (Customer $record): string => $record->phone)
                    ->searchable(['name', 'phone']),
                TextColumn::make('risk')
                    ->label('অবস্থা')
                    ->state(fn (Customer $record): CustomerRisk => CustomerRisk::for($record))
                    ->badge(),
                TextColumn::make('orders_count')->label('মোট অর্ডার')->sortable(),
                TextColumn::make('delivered_count')->label('ডেলিভারি')->sortable()->color('success'),
                TextColumn::make('cancelled_count')->label('বাতিল')->sortable()->color('danger'),
                TextColumn::make('last_order_at')->label('শেষ অর্ডার')->since()->placeholder('—')->sortable(),
                TextColumn::make('created_at')->label('যোগ দিয়েছেন')->date('d M Y')->sortable()->toggleable(isToggledHiddenByDefault: true),
                ToggleColumn::make('is_blocked')
                    ->label('ব্লক')
                    ->onColor('danger')
                    ->tooltip('ব্লক করলে এই কাস্টমার নতুন অর্ডার করতে পারবেন না'),
            ])
            ->filters([
                TernaryFilter::make('is_blocked')->label('ব্লক করা'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageCustomers::route('/'),
        ];
    }
}
