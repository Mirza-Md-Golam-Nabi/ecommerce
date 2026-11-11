import { Link } from '@inertiajs/react';
import { useEffect, useState } from 'react';

import AddToBagControl from '@/Components/AddToBagControl';
import { CloseIcon } from '@/Components/Icons';
import Price from '@/Components/Price';
import ProductGallery from '@/Components/ProductGallery';
import WeightedPriceNote from '@/Components/WeightedPriceNote';
import { useQuickView } from '@/contexts/QuickViewContext';
import { useModal } from '@/hooks/useModal';
import { requestJson } from '@/lib/api';
import type { ProductDetail } from '@/types';

/**
 * Product details over the listing (Chaldal style). Shows card data instantly, then loads
 * description and gallery from /quick-view/{slug}.
 */
export default function QuickViewModal() {
    const { product, close } = useQuickView();
    const [detail, setDetail] = useState<ProductDetail | null>(null);

    useModal(product !== null, close);

    useEffect(() => {
        setDetail(null);

        if (!product) {
            return;
        }

        let cancelled = false;

        requestJson<{ product: ProductDetail }>('get', `/quick-view/${product.slug}`)
            .then((response) => !cancelled && setDetail(response.product))
            .catch(() => undefined);

        return () => {
            cancelled = true;
        };
    }, [product]);

    if (!product) {
        return null;
    }

    return (
        <div className="fixed inset-0 z-[60] flex items-end justify-center sm:items-center sm:p-6" role="dialog" aria-modal="true" aria-label={product.name}>
            <div onClick={close} className="absolute inset-0 bg-ink/60 backdrop-blur-sm" />

            <div className="relative max-h-[92vh] w-full max-w-4xl overflow-y-auto rounded-t-3xl bg-card shadow-2xl sm:rounded-3xl">
                <button
                    type="button"
                    onClick={close}
                    aria-label="বন্ধ করুন"
                    className="absolute top-4 right-4 z-10 grid size-10 place-items-center rounded-full bg-surface text-ink shadow hover:bg-line"
                >
                    <CloseIcon className="size-5" />
                </button>

                <div className="grid gap-6 p-6 md:grid-cols-2 md:p-8">
                    <ProductGallery
                        images={detail?.images.length ? detail.images : [product.image]}
                        alt={product.name}
                        placeholderIcon={product.placeholder_icon}
                        discountPercent={product.discount_percent}
                    />

                    <div className="flex flex-col">
                        <h2 className="pr-10 text-2xl leading-snug font-bold text-ink">{product.name}</h2>
                        <p className="mt-1 text-muted">{product.unit_label}</p>
                        <div className="mt-4">
                            <Price price={product.price} compareAtPrice={product.compare_at_price} size="lg" />
                        </div>
                        {product.is_weighted && (
                            <div className="mt-3">
                                <WeightedPriceNote variant="callout" />
                            </div>
                        )}

                        <div className="mt-6">
                            <AddToBagControl product={product} size="lg" />
                        </div>

                        {detail?.description && <p className="mt-6 leading-relaxed whitespace-pre-line text-ink/80">{detail.description}</p>}

                        <Link href={`/${product.slug}`} className="mt-6 text-sm font-semibold text-primary underline-offset-4 hover:underline">
                            পণ্যের সম্পূর্ণ পাতা দেখুন →
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    );
}
