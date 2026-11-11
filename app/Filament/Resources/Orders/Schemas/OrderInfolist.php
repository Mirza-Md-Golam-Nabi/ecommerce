<?php

namespace App\Filament\Resources\Orders\Schemas;

use App\Enums\CustomerRisk;
use App\Filament\Infolists\MoneyEntry;
use App\Filament\Support\ProfitColor;
use App\Models\Order;
use App\Support\Money;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class OrderInfolist
{
    public static function configure(Schema $schema): Schema
    {
        /** Money with an "(আনুমানিক)" suffix until every actual purchase cost is entered. */
        $costAware = fn (Order $record, int $amount): string => Money::format($amount).($record->cost_is_final ? '' : ' (আনুমানিক)');

        return $schema
            ->columns(3)
            ->components([
                Section::make('অর্ডার')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('order_number')->label('অর্ডার নম্বর')->weight('bold')->copyable(),
                        TextEntry::make('status')->label('স্ট্যাটাস')->badge(),
                        TextEntry::make('placed_at')->label('অর্ডারের সময়')->dateTime('d M Y, h:i A'),
                        TextEntry::make('payment_status')->label('পেমেন্ট (COD)')->badge(),
                        TextEntry::make('cancel_reason')->label('বাতিলের কারণ')->badge()->color('danger')
                            ->visible(fn (Order $record): bool => $record->cancel_reason !== null),
                        MoneyEntry::make('collected_amount')->label('আদায় হয়েছে')
                            ->visible(fn (Order $record): bool => $record->collected_amount !== null),
                        TextEntry::make('admin_note')->label('অ্যাডমিন নোট')->placeholder('—')->columnSpanFull(),
                    ]),

                Section::make('কাস্টমার ও ঠিকানা')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('shipping_name')->label('প্রাপক'),
                        TextEntry::make('shipping_phone')->label('ফোন')
                            ->url(fn (Order $record): string => "tel:{$record->shipping_phone}")
                            ->copyable()
                            ->color('primary'),
                        TextEntry::make('area_name')->label('এলাকা'),
                        TextEntry::make('address_line')->label('ঠিকানা'),
                        TextEntry::make('customer_note')->label('কাস্টমারের নোট')->placeholder('—')->columnSpanFull(),
                        TextEntry::make('risk')->label('কাস্টমারের অবস্থা')
                            ->state(fn (Order $record): CustomerRisk => CustomerRisk::for($record->customer))
                            ->badge(),
                        TextEntry::make('customer_history')->label('আগের অর্ডার')
                            ->state(fn (Order $record): string => "মোট {$record->customer->orders_count} · ডেলিভারি {$record->customer->delivered_count} · বাতিল {$record->customer->cancelled_count}"),
                    ]),

                Section::make('হিসাব')
                    ->schema([
                        MoneyEntry::make('subtotal')->label('পণ্যের দাম'),
                        MoneyEntry::make('delivery_charge')->label('ডেলিভারি চার্জ'),
                        MoneyEntry::make('grand_total')->label('মোট বিল')->weight('bold')->size('lg'),
                        MoneyEntry::make('unavailable_total')->label('পাওয়া যায়নি (হারানো বিক্রি)')
                            ->visible(fn (Order $record): bool => $record->unavailable_total > 0)
                            ->color('danger'),
                        TextEntry::make('cost_total')->label('ক্রয়মূল্য')
                            ->formatStateUsing(fn (Order $record): string => $costAware($record, $record->cost_total)),
                        TextEntry::make('profit')->label('লাভ')
                            ->state(fn (Order $record): int => $record->grossProfit())
                            ->formatStateUsing(fn (Order $record): string => $costAware($record, $record->grossProfit()))
                            ->color(fn (Order $record): string => ProfitColor::for($record->grossProfit())),
                    ]),
            ]);
    }
}
