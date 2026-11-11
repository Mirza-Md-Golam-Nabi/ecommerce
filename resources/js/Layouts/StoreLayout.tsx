import { Link, usePage } from '@inertiajs/react';
import type { ReactNode } from 'react';

export default function StoreLayout({ children }: { children: ReactNode }) {
    const { store } = usePage().props;

    return (
        <div className="min-h-screen bg-surface">
            <header className="sticky top-0 z-30 bg-primary text-primary-fg shadow-md">
                <div className="mx-auto flex h-16 max-w-7xl items-center justify-between gap-4 px-4">
                    <Link href="/" className="flex items-center gap-2 text-xl font-bold tracking-wide">
                        <span className="grid size-9 place-items-center rounded-xl bg-accent text-accent-fg">✦</span>
                        {store.name}
                    </Link>
                    {store.phone && (
                        <a href={`tel:${store.phone}`} className="text-sm opacity-90 hover:opacity-100">
                            📞 {store.phone}
                        </a>
                    )}
                </div>
            </header>

            <main className="mx-auto max-w-7xl px-4 py-8">{children}</main>
        </div>
    );
}
