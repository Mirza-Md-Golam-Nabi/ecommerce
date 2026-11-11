<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum CancelReason: string implements HasLabel
{
    case CustomerRequest = 'customer_request';
    case Unreachable = 'unreachable';
    case RefusedAtDoor = 'refused_at_door';
    case AllUnavailable = 'all_unavailable';
    case FakeOrder = 'fake_order';
    case Other = 'other';

    public function getLabel(): string
    {
        return match ($this) {
            self::CustomerRequest => 'কাস্টমারের অনুরোধে',
            self::Unreachable => 'কাস্টমারকে পাওয়া যায়নি',
            self::RefusedAtDoor => 'ডেলিভারির সময় নেয়নি',
            self::AllUnavailable => 'কোনো পণ্য পাওয়া যায়নি',
            self::FakeOrder => 'ভুয়া অর্ডার',
            self::Other => 'অন্যান্য',
        };
    }
}
