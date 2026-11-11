import { Head } from '@inertiajs/react';

import AddToBagControl from '@/Components/AddToBagControl';
import Breadcrumbs from '@/Components/Breadcrumbs';
import Price from '@/Components/Price';
import ProductGallery from '@/Components/ProductGallery';
import WeightedPriceNote from '@/Components/WeightedPriceNote';
import type { Breadcrumb, ProductDetail } from '@/types';

export default function Product({ product, breadcrumbs }: { product: ProductDetail; breadcrumbs: Breadcrumb[] }) {
    return (
        <>
            <Head title={product.name} />
            <Breadcrumbs items={breadcrumbs} current={product.name} />

            <div className="grid gap-8 rounded-3xl border border-line bg-card p-5 shadow-sm md:grid-cols-2 md:p-8">
                <ProductGallery images={product.images} alt={product.name} placeholderIcon={product.placeholder_icon} discountPercent={product.discount_percent} />

                <div className="flex flex-col">
                    <h1 className="text-3xl leading-snug font-bold text-ink">{product.name}</h1>
                    <p className="mt-1 text-lg text-muted">{product.unit_label}</p>

                    <div className="mt-5">
                        <Price price={product.price} compareAtPrice={product.compare_at_price} size="lg" />
                    </div>

                    {product.is_weighted && (
                        <div className="mt-4">
                            <WeightedPriceNote variant="callout" />
                        </div>
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
