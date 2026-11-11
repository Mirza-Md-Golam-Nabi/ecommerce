<?php

namespace App\Filament\Tables;

use App\Services\Catalog\CategoryTree;
use Filament\Tables\Columns\TextColumn;

/**
 * Category id column shown as its full path ("খাবার › রান্না › মসলা") from the cached tree.
 */
final class CategoryPathColumn
{
    public static function make(string $name = 'category_id'): TextColumn
    {
        return TextColumn::make($name)
            ->label('ক্যাটাগরি')
            ->formatStateUsing(fn (int $state): string => app(CategoryTree::class)->pathLabel($state));
    }
}
