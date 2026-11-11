import { Head, InfiniteScroll, Link } from '@inertiajs/react';

import EmptyState from '@/Components/EmptyState';
import { ChevronRightIcon } from '@/Components/Icons';
import OrderStatusBadge from '@/Components/OrderStatusBadge';
import { formatDateTime, formatMoney, toBanglaDigits } from '@/lib/format';
import type { OrderListItem, ScrollData } from '@/types';

export default function OrdersIndex({ orders }: { orders: ScrollData<OrderListItem> }) {
    return (
        <>
            <Head title="আমার অর্ডার" />
            <h1 className="mb-6 text-2xl font-bold text-ink sm:text-3xl">আমার অর্ডার</h1>

            {orders.data.length === 0 ? (
                <EmptyState icon="🧾" title="এখনো কোনো অর্ডার করেননি">
                    <Link href="/" className="inline-block rounded-xl bg-primary px-5 py-2.5 font-semibold text-primary-fg">
                        কেনাকাটা শুরু করুন
                    </Link>
                </EmptyState>
            ) : (
                <InfiniteScroll data="orders">
                    <ul className="space-y-3">
                        {orders.data.map((order) => (
                            <li key={order.order_number}>
                                <Link
                                    href={`/orders/${order.order_number}`}
                                    className="flex flex-wrap items-center gap-x-6 gap-y-2 rounded-2xl border border-line bg-card p-4 shadow-sm transition hover:border-primary hover:shadow-md sm:p-5"
                                >
                                    <div className="min-w-0 flex-1">
                                        <p className="font-bold text-ink">{order.order_number}</p>
                                        <p className="text-sm text-muted">
                                            {formatDateTime(order.placed_at)} · {toBanglaDigits(order.items_count)}টি পণ্য
                                        </p>
                                    </div>
                                    <OrderStatusBadge status={order.status} />
                                    <span className="text-lg font-bold text-primary">{formatMoney(order.grand_total)}</span>
                                    <ChevronRightIcon className="hidden size-5 text-muted sm:block" />
                                </Link>
                            </li>
                        ))}
                    </ul>
                </InfiniteScroll>
            )}
        </>
    );
}
