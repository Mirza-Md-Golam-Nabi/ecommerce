<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Actions\Order\ChangeOrderStatus;
use App\Actions\Order\SetDeliveryCharge;
use App\Enums\CancelReason;
use App\Enums\OrderEventType;
use App\Enums\OrderStatus;
use App\Filament\Forms\MoneyInput;
use App\Filament\Resources\Orders\OrderResource;
use App\Filament\Support\DomainAction;
use App\Models\Order;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;

/**
 * Order processing screen: status buttons follow the lifecycle
 * pending → confirmed → purchasing → out_for_delivery → delivered (or cancelled).
 *
 * @property Order $record
 */
class ViewOrder extends ViewRecord
{
    protected static string $resource = OrderResource::class;

    public function getTitle(): string
    {
        return "অর্ডার {$this->record->order_number}";
    }

    protected function getHeaderActions(): array
    {
        return [
            $this->statusAction('confirm', OrderStatus::Confirmed, 'নিশ্চিত করুন', Heroicon::OutlinedPhone, 'কাস্টমারকে ফোন করে অর্ডারটি নিশ্চিত হয়েছেন?'),
            $this->statusAction('startPurchasing', OrderStatus::Purchasing, 'কেনাকাটা শুরু', Heroicon::OutlinedShoppingBag),
            $this->statusAction('outForDelivery', OrderStatus::OutForDelivery, 'ডেলিভারিতে পাঠান', Heroicon::OutlinedTruck),

            Action::make('markDelivered')
                ->label('ডেলিভারি সম্পন্ন')
                ->icon(Heroicon::OutlinedCheckBadge)
                ->color('success')
                ->visible(fn (): bool => $this->record->status->canTransitionTo(OrderStatus::Delivered))
                ->fillForm(fn (): array => ['collected_amount' => $this->record->grand_total])
                ->schema([
                    MoneyInput::make('collected_amount')->label('কত টাকা আদায় হয়েছে')->required(),
                ])
                ->action(fn (array $data) => $this->runAndRefresh(
                    fn () => app(ChangeOrderStatus::class)->handle($this->record, OrderStatus::Delivered, $this->admin(), collectedAmount: (int) $data['collected_amount']),
                    'ডেলিভারি সম্পন্ন হয়েছে',
                )),

            Action::make('cancel')
                ->label('বাতিল')
                ->icon(Heroicon::OutlinedXCircle)
                ->color('danger')
                ->visible(fn (): bool => $this->record->status->canTransitionTo(OrderStatus::Cancelled))
                ->schema([
                    Select::make('cancel_reason')->label('কারণ')->options(CancelReason::class)->required(),
                    Textarea::make('note')->label('নোট')->rows(2),
                ])
                ->action(fn (array $data) => $this->runAndRefresh(
                    fn () => app(ChangeOrderStatus::class)->handle(
                        $this->record,
                        OrderStatus::Cancelled,
                        $this->admin(),
                        $data['note'] ?? null,
                        $data['cancel_reason'] instanceof CancelReason ? $data['cancel_reason'] : CancelReason::from($data['cancel_reason']),
                    ),
                    'অর্ডার বাতিল হয়েছে',
                )),

            ActionGroup::make([
                Action::make('deliveryCharge')
                    ->label('ডেলিভারি চার্জ বদলান')
                    ->icon(Heroicon::OutlinedBanknotes)
                    ->visible(fn (): bool => $this->record->isEditable())
                    ->fillForm(fn (): array => ['delivery_charge' => $this->record->delivery_charge])
                    ->schema([
                        MoneyInput::make('delivery_charge')->label('ডেলিভারি চার্জ')->required(),
                        Textarea::make('note')->label('কারণ (ঐচ্ছিক)')->rows(2),
                    ])
                    ->action(fn (array $data) => $this->runAndRefresh(
                        fn () => app(SetDeliveryCharge::class)->handle($this->record, (int) $data['delivery_charge'], $this->admin(), $data['note'] ?? null),
                        'ডেলিভারি চার্জ আপডেট হয়েছে',
                    )),

                Action::make('adminNote')
                    ->label('অ্যাডমিন নোট')
                    ->icon(Heroicon::OutlinedPencilSquare)
                    ->fillForm(fn (): array => ['admin_note' => $this->record->admin_note])
                    ->schema([Textarea::make('admin_note')->label('নোট (শুধু অ্যাডমিন দেখবেন)')->rows(3)])
                    ->action(fn (array $data) => $this->runAndRefresh(function () use ($data): void {
                        $this->record->update(['admin_note' => $data['admin_note']]);
                        $this->record->events()->create([
                            'type' => OrderEventType::NoteAdded,
                            'note' => $data['admin_note'],
                            'user_id' => $this->admin()->id,
                        ]);
                    }, 'নোট সেভ হয়েছে')),

                Action::make('invoice')
                    ->label('ইনভয়েস প্রিন্ট')
                    ->icon(Heroicon::OutlinedPrinter)
                    ->url(fn (): string => route('admin.orders.invoice', $this->record))
                    ->openUrlInNewTab(),
            ])->label('আরও')->icon(Heroicon::OutlinedEllipsisVertical)->button()->color('gray'),
        ];
    }

    private function statusAction(string $name, OrderStatus $to, string $label, Heroicon $icon, ?string $confirmation = null): Action
    {
        return Action::make($name)
            ->label($label)
            ->icon($icon)
            ->color('primary')
            ->visible(fn (): bool => $this->record->status->canTransitionTo($to))
            ->requiresConfirmation()
            ->modalDescription($confirmation)
            ->action(fn () => $this->runAndRefresh(
                fn () => app(ChangeOrderStatus::class)->handle($this->record, $to, $this->admin()),
                "অর্ডার এখন: {$to->getLabel()}",
            ));
    }

    /**
     * Run a domain action, then reload the page so the infolist and relation managers show fresh data.
     */
    private function runAndRefresh(callable $callback, string $successTitle): void
    {
        if (DomainAction::run($callback(...), $successTitle)) {
            $this->redirect(OrderResource::getUrl('view', ['record' => $this->record]));
        }
    }

    private function admin(): User
    {
        /** @var User */
        return Auth::user();
    }
}
