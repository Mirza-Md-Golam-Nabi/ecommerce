<?php

namespace App\Filament\Resources\Areas;

use App\Filament\Resources\Areas\Pages\ManageAreas;
use App\Models\Area;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use UnitEnum;

/**
 * Delivery areas shown in the checkout dropdown ("অন্যান্য" is appended by the storefront).
 */
class AreaResource extends Resource
{
    protected static ?string $model = Area::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMapPin;

    protected static string|UnitEnum|null $navigationGroup = 'সেটিংস';

    protected static ?int $navigationSort = 10;

    protected static ?string $modelLabel = 'এলাকা';

    protected static ?string $pluralModelLabel = 'ডেলিভারি এলাকা';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('এলাকার নাম')
                    ->required()
                    ->maxLength(100),
                Toggle::make('is_active')
                    ->label('সক্রিয়')
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->columns([
                TextColumn::make('name')
                    ->label('এলাকা')
                    ->searchable()
                    ->sortable(),
                ToggleColumn::make('is_active')
                    ->label('সক্রিয়'),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('সক্রিয়'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageAreas::route('/'),
        ];
    }
}
