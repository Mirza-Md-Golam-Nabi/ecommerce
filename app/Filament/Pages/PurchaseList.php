<?php

namespace App\Filament\Pages;

use App\Actions\Order\ApplyPurchaseCosts;
use App\Actions\Order\ChangeOrderStatus;
use App\Actions\Order\MarkItemUnavailable;
use App\Enums\OrderStatus;
use App\Filament\Concerns\RunsOrderActions;
use App\Filament\Forms\MoneyInput;
use App\Filament\Support\DomainAction;
use App\Filament\Tables\CategoryPathColumn;
use App\Filament\Tables\MoneyColumn;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Support\Money;
use App\Support\Weight;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Pages\Page;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

/**
 * "কেনাকাটার তালিকা": what to buy in the market today, summed per product across all confirmed and
 * purchasing orders (OrderItem::toBuy). Entering the unit cost marks the product as bought.
 */
class PurchaseList extends Page implements HasTable
{
    use InteractsWithTable;
    use RunsOrderActions;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static string|UnitEnum|null $navigationGroup = 'অর্ডার';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'কেনাকাটার তালিকা';

    protected static ?string $title = 'কেনাকাটার তালিকা';

    public function getSubheading(): string
    {
        $estimate = (int) OrderItem::query()->toBuy()->selectRaw('sum(estimated_unit_cost * quantity) as total')->value('total');

        return 'নিশ্চিত ও কেনাকাটা-চলমান অর্ডারের যে পণ্যগুলো এখনো কেনা হয়নি। আনুমানিক খরচ: '.Money::format($estimate);
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([EmbeddedTable::make()]);
    }

    public function table(Table $table): Table
    {
        $toBuy = fn (Builder $query): Builder => $query->toBuy();

        return $table
            ->query(
                Product::withTrashed()
                    ->whereHas('orderItems', $toBuy)
                    ->withSum(['orderItems as to_buy_quantity' => $toBuy], 'quantity')
                    ->withCount(['orderItems as to_buy_orders' => $toBuy])
            )
            ->defaultSort('name')
            ->paginated(false)
            ->emptyStateHeading('এখন কিছু কেনার নেই')
            ->emptyStateDescription('অর্ডার নিশ্চিত করলে তার পণ্যগুলো এখানে আসবে।')
            ->columns([
                ImageColumn::make('image')->label('')->disk('public')->square(),
                TextColumn::make('name')
                    ->label('পণ্য')
                    ->description(fn (Product $record): string => $record->unit_label)
                    ->weight('bold')
                    ->searchable(),
                CategoryPathColumn::make()->toggleable(),
                TextColumn::make('to_buy_quantity')
                    ->label('কিনতে হবে')
                    ->formatStateUsing(fn (Product $record, int|string $state): string => $record->isWeighted()
                        ? "{$state} × {$record->unit_label} (≈ ".Weight::format((int) $state * (int) $record->nominal_weight_grams).')'
                        : "{$state}টি")
                    ->weight('bold')
                    ->size('lg'),
                TextColumn::make('to_buy_orders')->label('অর্ডার'),
                MoneyColumn::make('estimated_spend')
                    ->label('আনুমানিক খরচ')
                    ->state(fn (Product $record): int => $record->estimated_cost * (int) $record->to_buy_quantity),
            ])
            ->headerActions([
                Action::make('startPurchasing')
                    ->label(fn (): string => 'নিশ্চিত অর্ডারগুলো কেনাকাটায় নিন ('.Order::query()->where('status', OrderStatus::Confirmed)->count().')')
                    ->icon(Heroicon::OutlinedShoppingBag)
                    ->visible(fn (): bool => Order::query()->where('status', OrderStatus::Confirmed)->exists())
                    ->requiresConfirmation()
                    ->action(fn () => DomainAction::run(function (): void {
                        Order::query()->where('status', OrderStatus::Confirmed)->get()
                            ->each(fn (Order $order): Order => app(ChangeOrderStatus::class)->handle($order, OrderStatus::Purchasing, $this->admin()));
                    }, 'অর্ডারগুলো এখন "কেনাকাটা চলছে"')),
                Action::make('print')
                    ->label('প্রিন্ট')
                    ->icon(Heroicon::OutlinedPrinter)
                    ->color('gray')
                    ->alpineClickHandler('window.print()'),
            ])
            ->recordActions([
                Action::make('applyCost')
                    ->label('কেনা হয়েছে')
                    ->icon(Heroicon::OutlinedCheckBadge)
                    ->color('success')
                    ->button()
                    ->fillForm(fn (Product $record): array => ['unit_cost' => $record->estimated_cost])
                    ->schema(fn (Product $record): array => [
                        MoneyInput::make('unit_cost')
                            ->label("প্রতি এককের ({$record->unit_label}) ক্রয়মূল্য")
                            ->helperText($record->isWeighted() ? 'ওজনের পণ্যের আসল ওজন পরে অর্ডার পেজে দিতে হবে।' : 'সব অর্ডারে পুরো পরিমাণ কেনা হয়েছে ধরা হবে।')
                            ->required(),
                    ])
                    ->action(fn (Product $record, array $data) => DomainAction::run(
                        fn () => app(ApplyPurchaseCosts::class)->handle($record, (int) $data['unit_cost'], $this->admin()),
                        'ক্রয়মূল্য সেভ হয়েছে',
                    )),
                Action::make('markUnavailable')
                    ->label('পাওয়া যায়নি')
                    ->icon(Heroicon::OutlinedNoSymbol)
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalDescription('সব অর্ডার থেকে এই পণ্যটি বাদ যাবে এবং কাস্টমাররা বিনয়ী মেসেজ দেখবেন।')
                    ->schema([Textarea::make('note')->label('নোট (ঐচ্ছিক)')->rows(2)])
                    ->action(fn (Product $record, array $data) => DomainAction::run(function () use ($record, $data): void {
                        OrderItem::query()->toBuy()->where('product_id', $record->id)->get()
                            ->each(fn (OrderItem $item): OrderItem => app(MarkItemUnavailable::class)->handle($item, $data['note'] ?? null, $this->admin()));
                    }, 'পণ্যটি সব অর্ডারে "পাওয়া যায়নি" হয়েছে')),
            ]);
    }
}
