<?php

use App\Enums\OrderStatus;

it('follows the forward lifecycle', function () {
    expect(OrderStatus::Pending->canTransitionTo(OrderStatus::Confirmed))->toBeTrue()
        ->and(OrderStatus::Confirmed->canTransitionTo(OrderStatus::Purchasing))->toBeTrue()
        ->and(OrderStatus::Purchasing->canTransitionTo(OrderStatus::OutForDelivery))->toBeTrue()
        ->and(OrderStatus::OutForDelivery->canTransitionTo(OrderStatus::Delivered))->toBeTrue();
});

it('does not allow skipping steps or going backwards', function () {
    expect(OrderStatus::Pending->canTransitionTo(OrderStatus::Delivered))->toBeFalse()
        ->and(OrderStatus::Purchasing->canTransitionTo(OrderStatus::Confirmed))->toBeFalse();
});

it('allows cancelling any order that is not final', function (OrderStatus $status) {
    expect($status->canTransitionTo(OrderStatus::Cancelled))->toBeTrue();
})->with([
    'pending' => OrderStatus::Pending,
    'confirmed' => OrderStatus::Confirmed,
    'purchasing' => OrderStatus::Purchasing,
    'out for delivery' => OrderStatus::OutForDelivery,
]);

it('has no transitions out of final states', function (OrderStatus $status) {
    expect($status->isFinal())->toBeTrue()
        ->and($status->allowedTransitions())->toBe([]);
})->with([
    'delivered' => OrderStatus::Delivered,
    'cancelled' => OrderStatus::Cancelled,
]);

it('lets customers cancel only pending orders', function () {
    expect(OrderStatus::Pending->isCancellableByCustomer())->toBeTrue()
        ->and(OrderStatus::Confirmed->isCancellableByCustomer())->toBeFalse();
});
