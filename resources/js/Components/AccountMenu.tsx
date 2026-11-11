import { Link, usePage } from '@inertiajs/react';

import { UserIcon } from '@/Components/Icons';

/**
 * Header account area: "লগইন" for guests; "আমার অর্ডার" + logout for customers.
 */
export default function AccountMenu() {
    const { customer } = usePage().props.auth;

    if (!customer) {
        return (
            <Link
                href="/login"
                className="flex items-center gap-2 rounded-xl px-3 py-2 text-sm font-semibold whitespace-nowrap hover:bg-white/10"
                aria-label="লগইন"
            >
                <UserIcon className="size-5" />
                <span className="hidden sm:inline">লগইন</span>
            </Link>
        );
    }

    return (
        <div className="flex items-center gap-1">
            <Link
                href="/orders"
                className="flex items-center gap-2 rounded-xl px-3 py-2 text-sm font-semibold whitespace-nowrap hover:bg-white/10"
                aria-label="আমার অর্ডার"
            >
                <UserIcon className="size-5" />
                <span className="hidden max-w-32 truncate sm:inline">{customer.name}</span>
            </Link>
            <Link
                href="/logout"
                method="post"
                as="button"
                className="hidden rounded-xl px-3 py-2 text-sm whitespace-nowrap opacity-80 hover:bg-white/10 hover:opacity-100 md:block"
            >
                লগআউট
            </Link>
        </div>
    );
}
