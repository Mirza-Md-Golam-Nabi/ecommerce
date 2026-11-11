<?php

namespace App\Actions\Customer;

use App\Actions\Cart\MergeGuestBag;
use App\Models\Customer;
use Illuminate\Contracts\Session\Session;

/**
 * Everything that must happen right after a customer is authenticated (login or registration):
 * new session id (session fixation protection) and the guest bag moved into the customer's bag.
 */
final class CompleteCustomerLogin
{
    public function __construct(private MergeGuestBag $mergeGuestBag) {}

    public function handle(Customer $customer, Session $session, mixed $guestBagToken): void
    {
        $session->regenerate();

        $this->mergeGuestBag->handle($customer, $guestBagToken);
    }
}
