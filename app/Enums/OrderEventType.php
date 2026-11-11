<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum OrderEventType: string implements HasLabel
{
    case Placed = 'placed';
    case StatusChanged = 'status_changed';
    case DeliveryChargeChanged = 'delivery_charge_changed';
    case ItemUnavailable = 'item_unavailable';
    case ItemWeightSet = 'item_weight_set';
    case ItemQuantityChanged = 'item_quantity_changed';
    case ItemCostSet = 'item_cost_set';
    case NoteAdded = 'note_added';

    public function getLabel(): string
    {
        return match ($this) {
            self::Placed => 'অর্ডার করা হয়েছে',
            self::StatusChanged => 'স্ট্যাটাস পরিবর্তন',
            self::DeliveryChargeChanged => 'ডেলিভারি চার্জ পরিবর্তন',
            self::ItemUnavailable => 'পণ্য পাওয়া যায়নি',
            self::ItemWeightSet => 'ওজন নির্ধারণ',
            self::ItemQuantityChanged => 'পরিমাণ পরিবর্তন',
            self::ItemCostSet => 'ক্রয়মূল্য নির্ধারণ',
            self::NoteAdded => 'নোট',
        };
    }
}
