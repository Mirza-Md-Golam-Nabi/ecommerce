export interface StoreInfo {
    name: string;
    phone: string | null;
}

/** Props shared with every page by App\Http\Middleware\HandleInertiaRequests. */
export interface SharedProps {
    store: StoreInfo;
    [key: string]: unknown;
}

declare module '@inertiajs/core' {
    interface InertiaConfig {
        sharedPageProps: SharedProps;
    }
}
