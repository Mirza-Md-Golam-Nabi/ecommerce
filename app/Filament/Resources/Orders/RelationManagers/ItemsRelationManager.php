<?php

namespace App\Filament\Resources\Orders\RelationManagers;

use App\Actions\Order\MarkItemUnavailable;
use App\Actions\Order\SetItemCost;
use App\Actions\Order\SetItemFulfillment;
use App\Enums\OrderItemStatus;
use App\Filament\Forms\MoneyInput;
use App\Filament\Resources\Orders\OrderResource;
use App\Filament\Support\DomainAction;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use App\Support\Money;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

/**
 * Order lines with what was actually bought: weight / quantity / cost / unavailable.
 */
class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    protected static ?string $title = 'পণ্যসমূহ';

    public function table(Table $table): Table
    {
        $money = fn (?int $state): string => $state === null ? '—' : Money::format($state);

        return $table
            ->recordTitleAttribute('product_name')
            ->paginated(false)
            ->columns([
                TextColumn::make('product_name')
                    ->label('পণ্য')
                    ->description(fn (OrderItem $record): string => $record->unit_label)
                    ->weight('bold'),
                TextColumn::make('quantity')
                    ->label('অর্ডার')
                    ->formatStateUsing(fn (OrderItem $record): string => $record->isWeighted()
                        ? "{$record->quantity} × ≈ ".self::kg($record->quantity * (int) $record->nominal_weight_grams)
                        : "{$record->quantity}টি"),
                TextColumn::make('fulfilled')
                    ->label('কেনা হয়েছে')
                    ->state(fn (OrderItem $record): string => match (true) {
                        $record->status === OrderItemStatus::Unavailable => 'পাওয়া যায়নি',
                        $record->isWeighted() => $record->actual_weight_grams !== null ? self::kg($record->actual_weight_grams) : 'ওজন দেওয়া হয়নি',
                        default => $record->fulfilled_quantity !== null ? "{$record->fulfilled_quantity}টি" : '—',
                    }),
                TextColumn::make('unit_price')->label('একক দাম')->formatStateUsing($money),
                TextColumn::make('line_total')->label('মোট')->formatStateUsing($money)->weight('bold'),
                TextColumn::make('actual_unit_cost')
                    ->label('ক্রয়মূল্য (একক)')
                    ->state(fn (OrderItem $record): string => $record->actual_unit_cost !== null
                        ? Money::format($record->actual_unit_cost)
                        : Money::format($record->estimated_unit_cost).' (আনুমানিক)'),
                TextColumn::make('status')->label('অবস্থা')->badge(),
            ])
            ->recordActions([
                ActionGroup::make([
                    Action::make('setWeight')
                        ->label('আসল ওজন দিন')
                        ->icon(Heroicon::OutlinedScale)
                        ->visible(fn (OrderItem $record): bool => $record->isWeighted() && $this->order()->isEditable())
                        ->fillForm(fn (OrderItem $record): array => [
                            'kg' => ($record->actual_weight_grams ?? $record->quantity * (int) $record->nominal_weight_grams) / 1000,
                        ])
                        ->schema([
                            TextInput::make('kg')->label('ওজন')->suffix('কেজি')->numeric()->step(0.001)->minValue(0.001)->required(),
                        ])
                        ->action(fn (OrderItem $record, array $data) => $this->runAndRefresh(
                            fn () => app(SetItemFulfillment::class)->handle($record, (int) round(((float) $data['kg']) * 1000), $this->admin()),
                            'ওজন সেভ হয়েছে',
                        )),

                    Action::make('setQuantity')
                        ->label('কতটি কেনা হয়েছে')
                        ->icon(Heroicon::OutlinedCalculator)
                        ->visible(fn (OrderItem $record): bool => ! $record->isWeighted() && $this->order()->isEditable())
                        ->fillForm(fn (OrderItem $record): array => ['quantity' => $record->fulfilled_quantity ?? $record->quantity])
                        ->schema(fn (OrderItem $record): array => [
                            TextInput::make('quantity')->label('পরিমাণ')->integer()->minValue(1)->maxValue($record->quantity)->required()
                                ->helperText("অর্ডার করা হয়েছিল {$record->quantity}টি। কম হলে বাকিটা বিল থেকে বাদ যাবে।"),
                        ])
                        ->action(fn (OrderItem $record, array $data) => $this->runAndRefresh(
                            fn () => app(SetItemFulfillment::class)->handle($record, (int) $data['quantity'], $this->admin()),
                            'পরিমাণ সেভ হয়েছে',
                        )),

                    Action::make('setCost')
                        ->label('ক্রয়মূল্য দিন')
                        ->icon(Heroicon::OutlinedBanknotes)
                        ->visible(fn (): bool => $this->order()->isEditable())
                        ->fillForm(fn (OrderItem $record): array => ['unit_cost' => $record->actual_unit_cost ?? $record->estimated_unit_cost])
                        ->schema(fn (OrderItem $record): array => [
                            MoneyInput::make('unit_cost')->label("প্রতি এককের ({$record->unit_label}) ক্রয়মূল্য")->required(),
                        ])
                        ->action(fn (OrderItem $record, array $data) => $this->runAndRefresh(
                            fn () => app(SetItemCost::class)->handle($record, (int) $data['unit_cost'], $this->admin()),
                            'ক্রয়মূল্য সেভ হয়েছে',
                        )),

                    Action::make('markUnavailable')
                        ->label('পাওয়া যায়নি')
                        ->icon(Heroicon::OutlinedNoSymbol)
                        ->color('danger')
                        ->visible(fn (OrderItem $record): bool => $record->status !== OrderItemStatus::Unavailable && $this->order()->isEditable())
                        ->requiresConfirmation()
                        ->modalDescription('পণ্যটি বিল থেকে বাদ যাবে এবং কাস্টমার একটি বিনয়ী মেসেজ দেখবেন।')
                        ->schema([Textarea::make('note')->label('নোট (ঐচ্ছিক)')->rows(2)])
                        ->action(fn (OrderItem $record, array $data) => $this->runAndRefresh(
                            fn () => app(MarkItemUnavailable::class)->handle($record, $data['note'] ?? null, $this->admin()),
                            'পণ্যটি "পাওয়া যায়নি" হিসেবে চিহ্নিত হয়েছে',
                        )),
                ])->label('পরিবর্তন')->button()->size('sm'),
            ]);
    }

    private static function kg(int $grams): string
    {
        return rtrim(rtrim(number_format($grams / 1000, 3), '0'), '.').' কেজি';
    }

    private function order(): Order
    {
        /** @var Order */
        return $this->getOwnerRecord();
    }

    private function admin(): User
    {
        /** @var User */
        return Auth::user();
    }

    /**
     * Reload the order page so totals, timeline and buttons all reflect the change.
     */
    private function runAndRefresh(callable $callback, string $successTitle): void
    {
        if (DomainAction::run($callback(...), $successTitle)) {
            $this->redirect(OrderResource::getUrl('view', ['record' => $this->order()]));
        }
    }
}
