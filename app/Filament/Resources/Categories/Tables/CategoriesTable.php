<?php

namespace App\Filament\Resources\Categories\Tables;

use App\Models\Category;
use App\Services\Catalog\CategoryTree;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CategoriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->withCount(['children', 'products']))
            ->defaultSort('name')
            ->columns([
                ImageColumn::make('image')
                    ->label('')
                    ->disk('public')
                    ->circular(),
                TextColumn::make('name')
                    ->label('নাম')
                    ->formatStateUsing(fn (Category $record): string => $record->icon ? "{$record->icon} {$record->name}" : $record->name)
                    ->description(fn (Category $record): ?string => $record->parent_id ? app(CategoryTree::class)->pathLabel($record->parent_id) : null)
                    ->searchable()
                    ->sortable(),
                TextColumn::make('slug')
                    ->label('স্লাগ')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('children_count')
                    ->label('সাব-ক্যাটাগরি')
                    ->badge()
                    ->color('gray'),
                TextColumn::make('products_count')
                    ->label('পণ্য')
                    ->badge(),
                ToggleColumn::make('is_active')
                    ->label('সক্রিয়'),
                ToggleColumn::make('show_on_home')
                    ->label('হোমপেজে'),
            ])
            ->filters([
                SelectFilter::make('root')
                    ->label('শীর্ষ ক্যাটাগরি')
                    ->options(fn (): array => collect(app(CategoryTree::class)->children(null, onlyVisible: false))
                        ->mapWithKeys(fn (array $node): array => [$node['id'] => $node['name']])
                        ->all())
                    ->query(fn (Builder $query, array $data): Builder => filled($data['value'])
                        ? $query->where('path', 'like', '/'.(int) $data['value'].'/%')
                        : $query),
                TernaryFilter::make('is_active')
                    ->label('সক্রিয়'),
                TernaryFilter::make('show_on_home')
                    ->label('হোমপেজে'),
                TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->hidden(fn (Category $record): bool => $record->children_count > 0 || $record->products_count > 0),
                RestoreAction::make(),
            ]);
    }
}
