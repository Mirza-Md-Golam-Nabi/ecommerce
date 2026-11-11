import { Link, usePage } from '@inertiajs/react';

import AccountMenu from '@/Components/AccountMenu';
import { BagIcon, MenuIcon, PhoneIcon } from '@/Components/Icons';
import SearchBox from '@/Components/SearchBox';
import { useBag } from '@/contexts/BagContext';
import { toBanglaDigits } from '@/lib/format';

export default function Header({ onOpenMenu }: { onOpenMenu: () => void }) {
    const { store } = usePage().props;
    const bag = useBag();

    return (
        <header className="sticky top-0 z-40 bg-primary text-primary-fg shadow-lg">
            <div className="flex flex-wrap items-center gap-x-3 gap-y-2 px-4 py-3 lg:h-16 lg:flex-nowrap lg:py-0 lg:pr-6 lg:pl-5">
                <button
                    type="button"
                    onClick={onOpenMenu}
                    aria-label="ক্যাটাগরি মেনু"
                    className="grid size-10 place-items-center rounded-xl hover:bg-white/10 lg:hidden"
                >
                    <MenuIcon className="size-6" />
                </button>

                <Link href="/" className="flex items-center gap-2.5 text-xl font-bold tracking-wide whitespace-nowrap">
                    <span className="grid size-9 place-items-center rounded-xl bg-accent text-accent-fg shadow-md">✦</span>
                    {store.name}
                </Link>

                <div className="ml-auto flex items-center gap-1 lg:order-last lg:ml-0">
                    <AccountMenu />
                    <button
                        type="button"
                        onClick={bag.open}
                        aria-label="ব্যাগ দেখুন"
                        className="relative grid size-10 place-items-center rounded-xl hover:bg-white/10"
                    >
                        <BagIcon className="size-6" />
                        {bag.count > 0 && (
                            <span className="absolute -top-1 -right-1 grid min-w-5 place-items-center rounded-full bg-accent px-1 text-xs font-bold text-accent-fg">
                                {toBanglaDigits(bag.count)}
                            </span>
                        )}
                    </button>
                </div>

                <SearchBox className="order-last w-full lg:order-none lg:mx-auto lg:max-w-2xl lg:flex-1" />

                {store.phone && (
                    <a href={`tel:${store.phone}`} className="hidden items-center gap-2 text-sm whitespace-nowrap opacity-90 hover:opacity-100 xl:flex">
                        <PhoneIcon className="size-4" />
                        {toBanglaDigits(store.phone)}
                    </a>
                )}
            </div>
        </header>
    );
}
