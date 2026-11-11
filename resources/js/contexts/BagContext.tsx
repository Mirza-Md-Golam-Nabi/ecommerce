import { router, usePage } from '@inertiajs/react';
import { createContext, useCallback, useContext, useEffect, useMemo, useRef, useState, type ReactNode } from 'react';

import { useToast } from '@/contexts/ToastContext';
import { requestJson } from '@/lib/api';
import { toBanglaDigits } from '@/lib/format';
import type { BagSummary, ProductCard } from '@/types';

const SYNC_DELAY_MS = 300;

interface BagContextValue {
    count: number;
    /** Optimistic subtotal in poisha (reconciled with the server after each sync). */
    subtotal: number;
    quantityOf: (productId: number) => number;
    setQuantity: (product: ProductCard, quantity: number) => void;
    isOpen: boolean;
    open: () => void;
    close: () => void;
    /** Increments after every successful server sync; the bag panel refetches details on change. */
    version: number;
}

const BagContext = createContext<BagContextValue | null>(null);

function normalizeItems(items: BagSummary['items']): Record<number, number> {
    if (Array.isArray(items)) {
        return {};
    }

    return Object.fromEntries(Object.entries(items).map(([id, quantity]) => [Number(id), quantity]));
}

/**
 * Optimistic bag state (docs/08-storefront-ux.md "Bag behaviour").
 * The UI updates instantly; each product's absolute quantity is sent after a short debounce.
 * The server summary (shared `bag` prop) wins whenever no request is pending.
 */
export function BagProvider({ children }: { children: ReactNode }) {
    const serverBag = usePage().props.bag;
    const toast = useToast();

    const [quantities, setQuantities] = useState<Record<number, number>>(() => normalizeItems(serverBag.items));
    const [subtotal, setSubtotal] = useState(serverBag.subtotal);
    const [isOpen, setIsOpen] = useState(false);
    const [version, setVersion] = useState(0);

    const quantitiesRef = useRef(quantities);
    const timers = useRef(new Map<number, number>());
    const inFlight = useRef(0);

    const applyServerBag = useCallback((bag: BagSummary) => {
        const items = normalizeItems(bag.items);
        quantitiesRef.current = items;
        setQuantities(items);
        setSubtotal(bag.subtotal);
    }, []);

    const isIdle = () => timers.current.size === 0 && inFlight.current === 0;

    useEffect(() => {
        if (isIdle()) {
            applyServerBag(serverBag);
        }
    }, [serverBag, applyServerBag]);

    const sync = useCallback(
        async (productId: number, quantity: number) => {
            timers.current.delete(productId);
            inFlight.current++;

            try {
                const response = await requestJson<{ bag: BagSummary }>('put', `/bag/items/${productId}`, { quantity });
                inFlight.current--;

                if (isIdle()) {
                    applyServerBag(response.bag);
                }

                setVersion((current) => current + 1);
            } catch (error) {
                inFlight.current--;
                toast((error as Error).message, 'error');
                router.reload({ only: ['bag'] });
            }
        },
        [applyServerBag, toast],
    );

    const setQuantity = useCallback(
        (product: ProductCard, requested: number) => {
            const limit = product.max_quantity ?? 999;
            const quantity = Math.max(0, Math.min(requested, limit));
            const previous = quantitiesRef.current[product.id] ?? 0;

            if (requested > limit) {
                toast(`এক অর্ডারে সর্বোচ্চ ${toBanglaDigits(limit)}টি নেওয়া যাবে।`, 'error');
            }

            if (quantity === previous) {
                return;
            }

            const next = { ...quantitiesRef.current };

            if (quantity === 0) {
                delete next[product.id];
            } else {
                next[product.id] = quantity;
            }

            quantitiesRef.current = next;
            setQuantities(next);
            setSubtotal((current) => current + (quantity - previous) * product.price);

            window.clearTimeout(timers.current.get(product.id));
            timers.current.set(
                product.id,
                window.setTimeout(() => void sync(product.id, quantity), SYNC_DELAY_MS),
            );
        },
        [sync, toast],
    );

    const open = useCallback(() => setIsOpen(true), []);
    const close = useCallback(() => setIsOpen(false), []);

    const value = useMemo<BagContextValue>(
        () => ({
            count: Object.values(quantities).reduce((sum, quantity) => sum + quantity, 0),
            subtotal,
            quantityOf: (productId) => quantities[productId] ?? 0,
            setQuantity,
            isOpen,
            open,
            close,
            version,
        }),
        [quantities, subtotal, setQuantity, isOpen, open, close, version],
    );

    return <BagContext.Provider value={value}>{children}</BagContext.Provider>;
}

export function useBag() {
    const context = useContext(BagContext);

    if (!context) {
        throw new Error('useBag must be used inside BagProvider');
    }

    return context;
}
