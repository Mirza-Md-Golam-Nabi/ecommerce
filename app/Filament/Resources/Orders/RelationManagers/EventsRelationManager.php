<?php

namespace App\Filament\Resources\Orders\RelationManagers;

use App\Models\OrderEvent;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Read-only audit trail of the order (who changed what, when).
 */
class EventsRelationManager extends RelationManager
{
    protected static string $relationship = 'events';

    protected static ?string $title = 'ইতিহাস';

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with(['user', 'item']))
            ->defaultSort('id', 'desc')
            ->paginated([10, 25, 50])
            ->columns([
                TextColumn::make('created_at')
                    ->label('সময়')
                    ->dateTime('d M, h:i A'),
                TextColumn::make('type')
                    ->label('ঘটনা')
                    ->badge()
                    ->color('gray'),
                TextColumn::make('details')
                    ->label('বিস্তারিত')
                    ->state(fn (OrderEvent $record): string => $record->summary())
                    ->description(fn (OrderEvent $record): ?string => $record->item?->product_name)
                    ->wrap(),
                TextColumn::make('note')
                    ->label('নোট')
                    ->placeholder('—')
                    ->wrap(),
                TextColumn::make('user.name')
                    ->label('কে করেছেন')
                    ->placeholder('কাস্টমার / সিস্টেম'),
            ]);
    }
}
