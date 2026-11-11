import { InfiniteScroll } from '@inertiajs/react';

import ProductCard from '@/Components/ProductCard';
import type { ProductCard as ProductCardData } from '@/types';

interface ProductGridProps {
    /** Name of the Inertia::scroll() prop, e.g. "products". */
    data: string;
    products: ProductCardData[];
}

export const productGridClasses = 'grid grid-cols-2 gap-3 sm:grid-cols-3 sm:gap-4 xl:grid-cols-4 2xl:grid-cols-5';

export function ProductCardSkeleton() {
    return (
        <div className="animate-pulse overflow-hidden rounded-2xl border border-line bg-card">
            <div className="aspect-square bg-line/50" />
            <div className="space-y-2 p-3">
                <div className="h-4 w-4/5 rounded bg-line/70" />
                <div className="h-3 w-1/3 rounded bg-line/60" />
                <div className="h-5 w-1/2 rounded bg-line/70" />
                <div className="h-10 rounded-xl bg-line/60" />
            </div>
        </div>
    );
}

/**
 * Product grid that loads the next page automatically when scrolled to the end.
 */
export default function ProductGrid({ data, products }: ProductGridProps) {
    return (
        <InfiniteScroll
            data={data}
            buffer={400}
            loading={() => (
                <div className={`${productGridClasses} mt-4`}>
                    {Array.from({ length: 4 }, (_, index) => (
                        <ProductCardSkeleton key={index} />
                    ))}
                </div>
            )}
        >
            <div className={productGridClasses}>
                {products.map((product) => (
                    <ProductCard key={product.id} product={product} />
                ))}
            </div>
        </InfiniteScroll>
    );
}
