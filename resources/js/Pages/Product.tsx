import { Head } from '@inertiajs/react';
import { useState } from 'react';

import AddToBagControl from '@/Components/AddToBagControl';
import Breadcrumbs from '@/Components/Breadcrumbs';
import Price from '@/Components/Price';
import ProductImage from '@/Components/ProductImage';
import { toBanglaDigits } from '@/lib/format';
import type { Breadcrumb, ProductDetail } from '@/types';

export default function Product({ product, breadcrumbs }: { product: ProductDetail; breadcrumbs: Breadcrumb[] }) {
    const images = product.images.length > 0 ? product.images : [null];
    const [activeImage, setActiveImage] = useState(0);

    return (
        <>
            <Head title={product.name} />
            <Breadcrumbs items={breadcrumbs} current={product.name} />

            <div className="grid gap-8 rounded-3xl border border-line bg-card p-5 shadow-sm md:grid-cols-2 md:p-8">
                <div>
                    <div className="relative aspect-square overflow-hidden rounded-2xl border border-line bg-card p-6">
                        {product.discount_percent ? (
                            <span className="absolute top-4 left-4 z-10 rounded-full bg-accent px-3 py-1 font-bold text-accent-fg shadow">
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
                                    className={`size-20 shrink-0 overflow-hidden rounded-xl border-2 p-1 ${index === activeImage ? 'border-accent' : 'border-line'}`}
                                >
                                    <ProductImage src={image} alt="" />
                                </button>
                            ))}
                        </div>
                    )}
                </div>

                <div className="flex flex-col">
                    <h1 className="text-3xl leading-snug font-bold text-ink">{product.name}</h1>
                    <p className="mt-1 text-lg text-muted">{product.unit_label}</p>

                    <div className="mt-5">
                        <Price price={product.price} compareAtPrice={product.compare_at_price} size="lg" />
                    </div>

                    {product.is_weighted && (
                        <p className="mt-4 rounded-xl bg-accent/15 px-4 py-3 text-ink">⚖️ চূড়ান্ত দাম ডেলিভারির সময় আসল ওজন অনুযায়ী নির্ধারিত হবে।</p>
                    )}

                    <div className="mt-6 max-w-sm">
                        <AddToBagControl product={product} size="lg" />
                    </div>

                    <ul className="mt-8 space-y-2 text-sm text-muted">
                        <li>🚚 আপনার এলাকায় হোম ডেলিভারি</li>
                        <li>💵 পণ্য হাতে পেয়ে টাকা দিন</li>
                    </ul>

                    {product.description && (
                        <div className="mt-8 border-t border-line pt-6">
                            <h2 className="mb-2 font-semibold text-ink">বিবরণ</h2>
                            <p className="leading-relaxed whitespace-pre-line text-ink/80">{product.description}</p>
                        </div>
                    )}
                </div>
            </div>
        </>
    );
}
