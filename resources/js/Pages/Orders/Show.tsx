import { Head, Link, router } from '@inertiajs/react';

import { CheckIcon } from '@/Components/Icons';
import OrderStatusBadge from '@/Components/OrderStatusBadge';
import OrderTotals from '@/Components/OrderTotals';
import { COD_NOTE } from '@/lib/copy';
import { formatDateTime, formatMoney, formatWeight, toBanglaDigits } from '@/lib/format';
import type { CustomerOrder } from '@/types';

export default function OrderShow({ order }: { order: CustomerOrder }) {
    const isCancelled = order.status.value === 'cancelled';

    const cancel = () => {
        if (window.confirm('অর্ডারটি বাতিল করতে চান?')) {
            router.post(`/orders/${order.order_number}/cancel`, {}, { preserveScroll: true });
        }
    };

    return (
        <>
            <Head title={`অর্ডার ${order.order_number}`} />

            <div className="mb-6 flex flex-wrap items-center gap-3">
                <Link href="/orders" className="text-sm text-muted hover:text-primary">
                    ← আমার অর্ডার
                </Link>
            </div>

            <div className="mb-6 flex flex-wrap items-start justify-between gap-4">
                <div>
                    <h1 className="text-2xl font-bold text-ink sm:text-3xl">{order.order_number}</h1>
                    <p className="mt-1 text-muted">{formatDateTime(order.placed_at)}</p>
                </div>
                <div className="flex items-center gap-3">
                    <OrderStatusBadge status={order.status} />
                    {order.can_cancel && (
                        <button type="button" onClick={cancel} className="rounded-xl border border-danger/40 px-4 py-2 text-sm font-semibold text-danger hover:bg-danger/10">
                            অর্ডার বাতিল করুন
                        </button>
                    )}
                </div>
            </div>

            {isCancelled ? (
                <div className="mb-6 rounded-2xl border border-danger/30 bg-danger/10 px-5 py-4 text-danger">
                    অর্ডারটি বাতিল হয়েছে{order.cancel_reason ? ` — ${order.cancel_reason}` : ''}।
                </div>
            ) : (
                <ol className="mb-6 grid grid-cols-5 gap-1 rounded-2xl border border-line bg-card p-4 shadow-sm">
                    {order.timeline.map((step, index) => {
                        const done = step.at !== null;
                        const current = order.status.value === step.status;

                        return (
                            <li key={step.status} className="relative flex flex-col items-center text-center">
                                {index > 0 && <span className={`absolute top-4 right-1/2 h-0.5 w-full ${done ? 'bg-success' : 'bg-line'}`} />}
                                <span
                                    className={`relative grid size-8 place-items-center rounded-full text-sm font-bold ${
                                        done ? 'bg-success text-white' : 'bg-line text-muted'
                                    } ${current ? 'ring-4 ring-success/25' : ''}`}
                                >
                                    {done ? <CheckIcon className="size-4" /> : toBanglaDigits(index + 1)}
                                </span>
                                <span className={`mt-2 text-xs sm:text-sm ${done ? 'font-semibold text-ink' : 'text-muted'}`}>{step.label}</span>
                            </li>
                        );
                    })}
                </ol>
            )}

            <div className="grid items-start gap-6 lg:grid-cols-[1fr_340px]">
                <section className="rounded-3xl border border-line bg-card p-5 shadow-sm sm:p-6">
                    <h2 className="mb-4 text-lg font-bold text-ink">পণ্যসমূহ</h2>
                    <ul className="divide-y divide-line">
                        {order.items.map((item) => {
                            const unavailable = item.status === 'unavailable';

                            return (
                                <li key={item.id} className="py-3">
                                    <div className="flex items-start justify-between gap-4">
                                        <div className={unavailable ? 'opacity-60' : ''}>
                                            <p className={`font-semibold text-ink ${unavailable ? 'line-through' : ''}`}>{item.product_name}</p>
                                            <p className="text-sm text-muted">
                                                {item.unit_label} × {toBanglaDigits(item.quantity)} · {formatMoney(item.unit_price)}
                                                {item.is_weighted && item.actual_weight_grams !== null && ` · আসল ওজন ${formatWeight(item.actual_weight_grams)}`}
                                            </p>
                                        </div>
                                        <span className={`font-bold whitespace-nowrap ${unavailable ? 'text-muted line-through' : 'text-ink'}`}>
                                            {formatMoney(unavailable ? item.unit_price * item.quantity : item.line_total)}
                                        </span>
                                    </div>
                                    {item.message && <p className="mt-2 rounded-xl bg-accent/15 px-3 py-2 text-sm text-ink">{item.message}</p>}
                                </li>
                            );
                        })}
                    </ul>
                </section>

                <aside className="space-y-4">
                    <div className="rounded-3xl border border-line bg-card p-5 shadow-sm">
                        <OrderTotals
                            subtotal={order.subtotal}
                            deliveryCharge={order.delivery_charge}
                            total={order.grand_total}
                            isEstimated={order.is_estimated}
                        />
                        <p className="mt-3 text-sm text-muted">{COD_NOTE}</p>
                    </div>

                    <div className="rounded-3xl border border-line bg-card p-5 shadow-sm">
                        <h3 className="mb-2 font-bold text-ink">📍 ডেলিভারির ঠিকানা</h3>
                        <p className="font-semibold text-ink">{order.shipping.name}</p>
                        <p className="text-muted">{toBanglaDigits(order.shipping.phone)}</p>
                        <p className="mt-1 text-ink/80">
                            {order.shipping.address_line}, {order.shipping.area}
                        </p>
                        {order.customer_note && <p className="mt-2 text-sm text-muted">নোট: {order.customer_note}</p>}
                    </div>
                </aside>
            </div>
        </>
    );
}
