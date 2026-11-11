<?php

namespace App\Http\Middleware;

use App\Models\Customer;
use App\Services\Cart\CurrentBag;
use App\Services\Catalog\CategoryTree;
use App\Settings\StoreSettings;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'store' => fn (StoreSettings $settings): array => [
                'name' => $settings->storeName(),
                'phone' => $settings->storePhone(),
                'delivery_charge' => $settings->defaultDeliveryCharge(),
            ],
            'navigation' => Inertia::once(fn (): array => app(CategoryTree::class)->navigation()),
            'bag' => fn (): array => app(CurrentBag::class)->summary(),
            'auth' => fn (): array => [
                'customer' => ($customer = $request->user('customer')) instanceof Customer
                    ? ['id' => $customer->id, 'name' => $customer->name, 'phone' => $customer->phone]
                    : null,
            ],
            'flash' => fn (): array => [
                'success' => $request->session()->get('success'),
                'error' => $request->session()->get('error'),
            ],
        ];
    }
}
