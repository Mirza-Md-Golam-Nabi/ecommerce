<?php

namespace App\Filament\Resources\Products\Tables;

use App\Enums\SaleType;
use App\Filament\Tables\CategoryPathColumn;
use App\Filament\Tables\MoneyColumn;
use App\Models\Product;
use App\Services\Catalog\CategoryTree;
use App\Support\Money;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->columns([
                ImageColumn::make('image')
                    ->label('')
                    ->disk('public')
                    ->square(),
                TextColumn::make('name')
                    ->label('পণ্য')
                    ->description(fn (Product $record): string => $record->unit_label)
                    ->searchable(['name', 'slug', 'search_keywords'])
                    ->sortable(),
                CategoryPathColumn::make()->toggleable(),
                TextColumn::make('sale_type')
                    ->label('ধরন')
                    ->badge()
                    ->toggleable(),
                MoneyColumn::make('price')
                    ->label('দাম')
                    ->description(fn (Product $record): ?string => $record->compare_at_price ? 'আগে '.Money::format($record->compare_at_price) : null)
                    ->sortable(),
                MoneyColumn::make('estimated_cost')
                    ->label('আনুমানিক ক্রয়মূল্য')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                ToggleColumn::make('is_available')
                    ->label('বাজারে আছে'),
                ToggleColumn::make('is_active')
                    ->label('সক্রিয়'),
            ])
            ->filters([
                SelectFilter::make('category_id')
                    ->label('ক্যাটাগরি')
                    ->options(fn (): array => app(CategoryTree::class)->leafOptions())
                    ->searchable(),
                SelectFilter::make('sale_type')
                    ->label('ধরন')
                    ->options(SaleType::class),
                TernaryFilter::make('is_available')
                    ->label('বাজারে আছে'),
                TernaryFilter::make('is_active')
                    ->label('সক্রিয়'),
                TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
                RestoreAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('markAvailable')
                        ->label('বাজারে আছে')
                        ->icon(Heroicon::OutlinedCheckCircle)
                        ->action(fn (Collection $records): int => Product::query()->whereKey($records->modelKeys())->update(['is_available' => true]))
                        ->deselectRecordsAfterCompletion(),
                    BulkAction::make('markUnavailable')
                        ->label('বাজারে নেই')
                        ->icon(Heroicon::OutlinedXCircle)
                        ->color('danger')
                        ->action(fn (Collection $records): int => Product::query()->whereKey($records->modelKeys())->update(['is_available' => false]))
                        ->deselectRecordsAfterCompletion(),
                    DeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
