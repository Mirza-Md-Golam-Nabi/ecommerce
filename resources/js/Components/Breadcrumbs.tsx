import { Link } from '@inertiajs/react';
import { Fragment } from 'react';

import { ChevronRightIcon } from '@/Components/Icons';
import type { Breadcrumb } from '@/types';

interface BreadcrumbsProps {
    items: Breadcrumb[];
    /** Optional last, non-linked item (e.g. the product name). */
    current?: string;
}

export default function Breadcrumbs({ items, current }: BreadcrumbsProps) {
    return (
        <nav aria-label="পথ" className="mb-4 flex flex-wrap items-center gap-1 text-sm text-muted">
            <Link href="/" className="hover:text-primary">
                হোম
            </Link>
            {items.map((item, index) => {
                const isLast = index === items.length - 1 && !current;

                return (
                    <Fragment key={item.slug}>
                        <ChevronRightIcon className="size-4 opacity-60" />
                        {isLast ? (
                            <span className="font-medium text-ink">{item.name}</span>
                        ) : (
                            <Link href={`/${item.slug}`} className="hover:text-primary">
                                {item.name}
                            </Link>
                        )}
                    </Fragment>
                );
            })}
            {current ? (
                <>
                    <ChevronRightIcon className="size-4 opacity-60" />
                    <span className="line-clamp-1 font-medium text-ink">{current}</span>
                </>
            ) : null}
        </nav>
    );
}
