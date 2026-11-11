<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Settings\StoreSettings;
use Filament\Facades\Filament;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Printable invoice for the delivery person (admins only, `web` guard).
 */
class OrderInvoiceController extends Controller
{
    public function __invoke(Order $order, StoreSettings $settings): View|RedirectResponse
    {
        if (! Auth::guard('web')->check()) {
            return redirect()->guest(Filament::getPanel('admin')->getLoginUrl());
        }

        return view('admin.invoice', [
            'order' => $order->load('items'),
            'storeName' => $settings->storeName(),
            'storePhone' => $settings->storePhone(),
        ]);
    }
}
