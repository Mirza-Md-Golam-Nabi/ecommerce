import { Deferred, Head } from '@inertiajs/react';

import AddToBagControl from '@/Components/AddToBagControl';
import Breadcrumbs from '@/Components/Breadcrumbs';
import Price from '@/Components/Price';
import ProductCard from '@/Components/ProductCard';
import ProductGallery from '@/Components/ProductGallery';
import { ProductCardSkeleton, productGridClasses } from '@/Components/ProductGrid';
import WeightedPriceNote from '@/Components/WeightedPriceNote';
import type { Breadcrumb, ProductCard as ProductCardData, ProductDetail } from '@/types';

interface ProductPageProps {
    product: ProductDetail;
    breadcrumbs: Breadcrumb[];
    /** Deferred: loaded right after the page renders. */
    relatedProducts?: ProductCardData[];
}

function RelatedProductsHeading() {
    return (
        <div className="mb-5">
            <h2 className="text-2xl font-bold text-ink">সম্পর্কিত পণ্য</h2>
            <div className="mt-2 h-1 w-16 rounded-full bg-accent" />
        </div>
    );
}

function RelatedProducts({ products }: { products: ProductCardData[] | undefined }) {
    if (!products || products.length === 0) {
        return null;
    }

    return (
        <section className="mt-10">
            <RelatedProductsHeading />
            <div className={productGridClasses}>
                {products.map((related) => (
                    <ProductCard key={related.id} product={related} />
                ))}
            </div>
        </section>
    );
}

export default function Product({ product, breadcrumbs, relatedProducts }: ProductPageProps) {
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

            <Deferred
                data="relatedProducts"
                fallback={
                    <section className="mt-10">
                        <RelatedProductsHeading />
                        <div className={productGridClasses}>
                            {Array.from({ length: 5 }, (_, index) => (
                                <ProductCardSkeleton key={index} />
                            ))}
                        </div>
                    </section>
                }
            >
                <RelatedProducts products={relatedProducts} />
            </Deferred>
        </>
    );
}
