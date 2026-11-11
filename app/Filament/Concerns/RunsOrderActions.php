<?php

namespace App\Filament\Concerns;

use App\Filament\Resources\Orders\OrderResource;
use App\Filament\Support\DomainAction;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

/**
 * Shared by Filament pages/components that call order Actions: the acting admin, and
 * "run the action, notify, then reload the order page" (docs/decisions.md D-031).
 */
trait RunsOrderActions
{
    protected function admin(): User
    {
        /** @var User */
        return Auth::user();
    }

    /**
     * @param  callable(): mixed  $callback
     */
    protected function runAndReloadOrder(Order $order, callable $callback, string $successTitle): void
    {
        if (DomainAction::run($callback(...), $successTitle)) {
            $this->redirect(OrderResource::getUrl('view', ['record' => $order]));
        }
    }
}
