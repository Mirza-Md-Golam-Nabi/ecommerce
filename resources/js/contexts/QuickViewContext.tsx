import { router } from '@inertiajs/react';
import { createContext, useCallback, useContext, useEffect, useMemo, useState, type ReactNode } from 'react';

import type { ProductCard } from '@/types';

interface QuickViewContextValue {
    product: ProductCard | null;
    open: (product: ProductCard) => void;
    close: () => void;
}

const QuickViewContext = createContext<QuickViewContextValue | null>(null);

export function QuickViewProvider({ children }: { children: ReactNode }) {
    const [product, setProduct] = useState<ProductCard | null>(null);

    useEffect(() => router.on('navigate', () => setProduct(null)), []);

    const close = useCallback(() => setProduct(null), []);

    const value = useMemo<QuickViewContextValue>(
        () => ({
            product,
            open: setProduct,
            close,
        }),
        [product, close],
    );

    return <QuickViewContext.Provider value={value}>{children}</QuickViewContext.Provider>;
}

export function useQuickView() {
    const context = useContext(QuickViewContext);

    if (!context) {
        throw new Error('useQuickView must be used inside QuickViewProvider');
    }

    return context;
}
