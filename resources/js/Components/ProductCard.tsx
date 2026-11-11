import AddToBagControl from '@/Components/AddToBagControl';
import DiscountBadge from '@/Components/DiscountBadge';
import Price from '@/Components/Price';
import ProductImage from '@/Components/ProductImage';
import { useQuickView } from '@/contexts/QuickViewContext';
import type { ProductCard as ProductCardData } from '@/types';

export default function ProductCard({ product }: { product: ProductCardData }) {
    const quickView = useQuickView();

    return (
        <article
            className={`group relative flex flex-col overflow-hidden rounded-2xl border border-line bg-card shadow-sm transition duration-200 hover:-translate-y-1 hover:shadow-xl ${
                product.is_available ? '' : 'opacity-75'
            }`}
        >
            <DiscountBadge percent={product.discount_percent} />

            <button
                type="button"
                onClick={() => quickView.open(product)}
                className="block aspect-square overflow-hidden bg-card p-4"
                aria-label={`${product.name} বিস্তারিত দেখুন`}
            >
                <div className="size-full transition duration-300 group-hover:scale-105">
                    <ProductImage src={product.image} alt={product.name} placeholderIcon={product.placeholder_icon} />
                </div>
            </button>

            <div className="flex flex-1 flex-col px-3 pb-3">
                <button
                    type="button"
                    onClick={() => quickView.open(product)}
                    className="line-clamp-2 text-left leading-snug font-semibold text-ink transition hover:text-primary"
                >
                    {product.name}
                </button>
                <p className="mt-0.5 text-sm text-muted">{product.unit_label}</p>
                <div className="mt-2">
                    <Price price={product.price} compareAtPrice={product.compare_at_price} />
                </div>
                <div className="mt-auto pt-3">
                    <AddToBagControl product={product} />
                </div>
            </div>
        </article>
    );
}
