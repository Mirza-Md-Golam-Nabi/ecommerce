import { Link } from '@inertiajs/react';
import { useEffect, useState } from 'react';

import AddToBagControl from '@/Components/AddToBagControl';
import { CloseIcon } from '@/Components/Icons';
import Price from '@/Components/Price';
import ProductImage from '@/Components/ProductImage';
import { useQuickView } from '@/contexts/QuickViewContext';
import { requestJson } from '@/lib/api';
import { toBanglaDigits } from '@/lib/format';
import type { ProductDetail } from '@/types';

/**
 * Product details over the listing (Chaldal style). Shows card data instantly, then loads
 * description and gallery from /quick-view/{slug}.
 */
export default function QuickViewModal() {
    const { product, close } = useQuickView();
    const [detail, setDetail] = useState<ProductDetail | null>(null);
    const [activeImage, setActiveImage] = useState(0);

    useEffect(() => {
        setDetail(null);
        setActiveImage(0);

        if (!product) {
            return;
        }

        let cancelled = false;

        requestJson<{ product: ProductDetail }>('get', `/quick-view/${product.slug}`)
            .then((response) => !cancelled && setDetail(response.product))
            .catch(() => undefined);

        const onKey = (event: KeyboardEvent) => event.key === 'Escape' && close();
        document.body.style.overflow = 'hidden';
        window.addEventListener('keydown', onKey);

        return () => {
            cancelled = true;
            document.body.style.overflow = '';
            window.removeEventListener('keydown', onKey);
        };
    }, [product, close]);

    if (!product) {
        return null;
    }

    const images = detail?.images.length ? detail.images : [product.image];

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
                    <div>
                        <div className="relative aspect-square overflow-hidden rounded-2xl border border-line bg-card p-6">
                            {product.discount_percent ? (
                                <span className="absolute top-3 left-3 z-10 rounded-full bg-accent px-3 py-1 text-sm font-bold text-accent-fg">
                                    -{toBanglaDigits(product.discount_percent)}%
                                </span>
                            ) : null}
                            <ProductImage src={images[activeImage] ?? null} alt={product.name} placeholderIcon={product.placeholder_icon} />
                        </div>
                        {images.length > 1 && (
                            <div className="mt-3 flex gap-2 overflow-x-auto">
                                {images.map((image, index) => (
                                    <button
                                        key={image ?? index}
                                        type="button"
                                        onClick={() => setActiveImage(index)}
                                        className={`size-16 shrink-0 overflow-hidden rounded-xl border-2 p-1 ${index === activeImage ? 'border-accent' : 'border-line'}`}
                                    >
                                        <ProductImage src={image} alt="" />
                                    </button>
                                ))}
                            </div>
                        )}
                    </div>

                    <div className="flex flex-col">
                        <h2 className="pr-10 text-2xl leading-snug font-bold text-ink">{product.name}</h2>
                        <p className="mt-1 text-muted">{product.unit_label}</p>
                        <div className="mt-4">
                            <Price price={product.price} compareAtPrice={product.compare_at_price} size="lg" />
                        </div>
                        {product.is_weighted && (
                            <p className="mt-3 rounded-xl bg-accent/15 px-4 py-2 text-sm text-ink">⚖️ চূড়ান্ত দাম ডেলিভারির সময় আসল ওজন অনুযায়ী নির্ধারিত হবে।</p>
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
