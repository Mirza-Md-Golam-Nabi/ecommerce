import { useEffect, useState } from 'react';

import DiscountBadge from '@/Components/DiscountBadge';
import ProductImage from '@/Components/ProductImage';

interface ProductGalleryProps {
    images: (string | null)[];
    alt: string;
    placeholderIcon: string | null;
    discountPercent: number | null;
}

/**
 * Main image with discount badge and clickable thumbnails (product page and quick view).
 */
export default function ProductGallery({ images, alt, placeholderIcon, discountPercent }: ProductGalleryProps) {
    const list = images.length > 0 ? images : [null];
    const [active, setActive] = useState(0);

    useEffect(() => setActive(0), [images]);

    return (
        <div>
            <div className="relative aspect-square overflow-hidden rounded-2xl border border-line bg-card p-6">
                <DiscountBadge percent={discountPercent} size="lg" />
                <ProductImage src={list[active] ?? null} alt={alt} placeholderIcon={placeholderIcon} />
            </div>
            {list.length > 1 && (
                <div className="mt-3 flex gap-2 overflow-x-auto">
                    {list.map((image, index) => (
                        <button
                            key={image ?? index}
                            type="button"
                            onClick={() => setActive(index)}
                            className={`size-16 shrink-0 overflow-hidden rounded-xl border-2 p-1 sm:size-20 ${index === active ? 'border-accent' : 'border-line'}`}
                        >
                            <ProductImage src={image} alt="" />
                        </button>
                    ))}
                </div>
            )}
        </div>
    );
}
