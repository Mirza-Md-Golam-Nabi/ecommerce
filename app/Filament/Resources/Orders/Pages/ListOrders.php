<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Enums\OrderStatus;
use App\Filament\Resources\Orders\OrderResource;
use App\Models\Order;
use App\Support\BanglaNumber;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListOrders extends ListRecords
{
    protected static string $resource = OrderResource::class;

    /** @var array<string, int>|null */
    private ?array $statusCounts = null;

    /**
     * @return array<string, Tab>
     */
    public function getTabs(): array
    {
        $tabs = [
            'active' => Tab::make('চলমান')
                ->badge($this->countLabel(array_map(fn (OrderStatus $status): string => $status->value, $this->activeStatuses())))
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->whereIn('status', $this->activeStatuses())),
        ];

        foreach (OrderStatus::cases() as $status) {
            $tabs[$status->value] = Tab::make($status->getLabel())
                ->badge($this->countLabel([$status->value]))
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('status', $status));
        }

        return $tabs + ['all' => Tab::make('সব')];
    }

    public function getDefaultActiveTab(): string
    {
        return 'active';
    }

    /**
     * @return list<OrderStatus>
     */
    private function activeStatuses(): array
    {
        return array_values(array_filter(OrderStatus::cases(), fn (OrderStatus $status): bool => ! $status->isFinal()));
    }

    /**
     * @param  list<string>  $statuses
     */
    private function countLabel(array $statuses): ?string
    {
        $this->statusCounts ??= Order::query()
            ->selectRaw('status, count(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status')
            ->map(fn (mixed $count): int => (int) $count)
            ->all();

        $count = array_sum(array_intersect_key($this->statusCounts, array_flip($statuses)));

        return $count > 0 ? BanglaNumber::digits($count) : null;
    }
}
