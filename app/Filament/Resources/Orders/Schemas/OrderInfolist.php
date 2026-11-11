<?php

namespace App\Filament\Resources\Orders\Schemas;

use App\Enums\CustomerRisk;
use App\Models\Order;
use App\Support\Money;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class OrderInfolist
{
    public static function configure(Schema $schema): Schema
    {
        $money = fn (?int $state): string => Money::format((int) $state);

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
                        TextEntry::make('collected_amount')->label('আদায় হয়েছে')->formatStateUsing($money)
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
                        TextEntry::make('subtotal')->label('পণ্যের দাম')->formatStateUsing($money),
                        TextEntry::make('delivery_charge')->label('ডেলিভারি চার্জ')->formatStateUsing($money),
                        TextEntry::make('grand_total')->label('মোট বিল')->formatStateUsing($money)->weight('bold')->size('lg'),
                        TextEntry::make('unavailable_total')->label('পাওয়া যায়নি (হারানো বিক্রি)')->formatStateUsing($money)
                            ->visible(fn (Order $record): bool => $record->unavailable_total > 0)
                            ->color('danger'),
                        TextEntry::make('cost_total')->label('ক্রয়মূল্য')
                            ->formatStateUsing(fn (Order $record): string => Money::format($record->cost_total).($record->cost_is_final ? '' : ' (আনুমানিক)')),
                        TextEntry::make('profit')->label('লাভ')
                            ->state(fn (Order $record): int => $record->subtotal - $record->cost_total)
                            ->formatStateUsing(fn (Order $record, int $state): string => Money::format($state).($record->cost_is_final ? '' : ' (আনুমানিক)'))
                            ->color(fn (int $state): string => $state >= 0 ? 'success' : 'danger'),
                    ]),
            ]);
    }
}
