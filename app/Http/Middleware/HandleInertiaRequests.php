<?php

namespace App\Http\Middleware;

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
            'store' => fn (): array => [
                'name' => app(StoreSettings::class)->storeName(),
                'phone' => app(StoreSettings::class)->storePhone(),
                'delivery_charge' => app(StoreSettings::class)->defaultDeliveryCharge(),
            ],
            'navigation' => Inertia::once(fn (): array => app(CategoryTree::class)->navigation()),
            'bag' => fn (): array => app(CurrentBag::class)->summary(),
        ];
    }
}
