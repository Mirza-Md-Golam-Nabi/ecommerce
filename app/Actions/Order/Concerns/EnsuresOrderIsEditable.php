<?php

namespace App\Actions\Order\Concerns;

use App\Models\Order;
use Illuminate\Validation\ValidationException;

trait EnsuresOrderIsEditable
{
    /**
     * @throws ValidationException
     */
    protected function ensureEditable(Order $order): void
    {
        if (! $order->isEditable()) {
            throw ValidationException::withMessages([
                'order' => "\"{$order->status->getLabel()}\" অর্ডার আর বদলানো যায় না।",
            ]);
        }
    }
}
