import { Link, usePage } from '@inertiajs/react';
import { useEffect, useMemo, useState } from 'react';

import AddToBagControl from '@/Components/AddToBagControl';
import IconButton from '@/Components/IconButton';
import { BagIcon, CloseIcon, TrashIcon } from '@/Components/Icons';
import OrderTotals from '@/Components/OrderTotals';
import ProductImage from '@/Components/ProductImage';
import { useBag } from '@/contexts/BagContext';
import { useToast } from '@/contexts/ToastContext';
import { useModal } from '@/hooks/useModal';
import { requestJson } from '@/lib/api';
import { COD_NOTE } from '@/lib/copy';
import { formatMoney, toBanglaDigits } from '@/lib/format';
import type { BagDetails } from '@/types';

/**
 * Slide-over bag. Product list comes from GET /bag; quantities and totals come from the
 * optimistic bag context so the panel reacts instantly.
 */
export default function BagPanel() {
    const bag = useBag();
    const toast = useToast();
    const { store } = usePage().props;
    const [details, setDetails] = useState<BagDetails | null>(null);

    useEffect(() => {
        if (!bag.isOpen) {
            return;
        }

        let cancelled = false;

        requestJson<BagDetails>('get', '/bag')
            .then((response) => !cancelled && setDetails(response))
            .catch((error: Error) => toast(error.message, 'error'));

        return () => {
            cancelled = true;
        };
    }, [bag.isOpen, bag.version, toast]);

    useModal(bag.isOpen, bag.close);

    const lines = useMemo(
        () => (details?.items ?? []).filter((line) => bag.quantityOf(line.product.id) > 0 || !line.can_be_ordered),
        [details, bag],
    );

    const hasWeighted = lines.some((line) => line.can_be_ordered && line.product.is_weighted);
    const deliveryCharge = bag.subtotal > 0 ? store.delivery_charge : 0;

    return (
        <div className={`fixed inset-0 z-50 ${bag.isOpen ? '' : 'pointer-events-none'}`} aria-hidden={!bag.isOpen}>
            <div onClick={bag.close} className={`absolute inset-0 bg-ink/50 backdrop-blur-sm transition-opacity ${bag.isOpen ? 'opacity-100' : 'opacity-0'}`} />

            <aside
                role="dialog"
                aria-label="আপনার ব্যাগ"
                className={`absolute top-0 right-0 flex h-full w-full max-w-md flex-col bg-surface shadow-2xl transition-transform duration-300 ${
                    bag.isOpen ? 'translate-x-0' : 'translate-x-full'
                }`}
            >
                <div className="flex items-center justify-between bg-primary px-5 py-4 text-primary-fg">
                    <h2 className="flex items-center gap-2 text-lg font-bold">
                        <BagIcon className="size-6" />
                        আপনার ব্যাগ
                        <span className="rounded-full bg-accent px-2 text-sm text-accent-fg">{toBanglaDigits(bag.count)}</span>
                    </h2>
                    <button type="button" onClick={bag.close} aria-label="বন্ধ করুন" className="grid size-9 place-items-center rounded-full hover:bg-white/10">
                        <CloseIcon className="size-6" />
                    </button>
                </div>

                <div className="flex-1 space-y-3 overflow-y-auto p-4">
                    {details === null && bag.count > 0 && <p className="py-10 text-center text-muted">লোড হচ্ছে…</p>}

                    {bag.count === 0 && lines.length === 0 && (
                        <div className="flex flex-col items-center py-16 text-center">
                            <span className="text-6xl">🛍️</span>
                            <p className="mt-4 text-lg font-semibold text-ink">আপনার ব্যাগ খালি</p>
                            <p className="mt-1 text-muted">পছন্দের পণ্য ব্যাগে যোগ করুন।</p>
                        </div>
                    )}

                    {lines.map((line) => {
                        const quantity = bag.quantityOf(line.product.id);

                        return (
                            <div key={line.product.id} className="flex gap-3 rounded-2xl border border-line bg-card p-3 shadow-sm">
                                <div className="size-20 shrink-0 overflow-hidden rounded-xl">
                                    <ProductImage src={line.product.image} alt={line.product.name} placeholderIcon={line.product.placeholder_icon} />
                                </div>
                                <div className="min-w-0 flex-1">
                                    <div className="flex items-start justify-between gap-2">
                                        <div className="min-w-0">
                                            <p className="line-clamp-2 leading-snug font-semibold text-ink">{line.product.name}</p>
                                            <p className="text-sm text-muted">
                                                {line.product.unit_label} · {formatMoney(line.product.price)}
                                            </p>
                                        </div>
                                        <IconButton
                                            label="মুছুন"
                                            tone="danger"
                                            icon={<TrashIcon className="size-5" />}
                                            onClick={() => bag.setQuantity(line.product, 0)}
                                        />
                                    </div>

                                    {line.can_be_ordered ? (
                                        <div className="mt-2 flex items-center gap-3">
                                            <div className="w-36">
                                                <AddToBagControl product={line.product} />
                                            </div>
                                            <span className="ml-auto font-bold text-primary">{formatMoney(line.product.price * quantity)}</span>
                                        </div>
                                    ) : (
                                        <p className="mt-2 rounded-lg bg-danger/10 px-3 py-1.5 text-sm text-danger">
                                            দুঃখিত, পণ্যটি এখন পাওয়া যাচ্ছে না — অর্ডারে যোগ হবে না।
                                        </p>
                                    )}
                                </div>
                            </div>
                        );
                    })}
                </div>

                {bag.count > 0 && (
                    <div className="space-y-2 border-t border-line bg-card p-5">
                        <OrderTotals subtotal={bag.subtotal} deliveryCharge={deliveryCharge} total={bag.subtotal + deliveryCharge} isEstimated={hasWeighted} />
                        {bag.subtotal > 0 ? (
                            <Link
                                href="/checkout"
                                onClick={bag.close}
                                className="mt-2 flex h-12 w-full items-center justify-center rounded-xl bg-accent font-bold text-accent-fg shadow transition hover:brightness-105"
                            >
                                অর্ডার করুন
                            </Link>
                        ) : (
                            <p className="mt-2 rounded-xl bg-danger/10 px-3 py-2 text-center text-sm text-danger">অর্ডার করার মতো পণ্য ব্যাগে নেই।</p>
                        )}
                        <p className="text-center text-xs text-muted">{COD_NOTE}</p>
                    </div>
                )}
            </aside>
        </div>
    );
}
