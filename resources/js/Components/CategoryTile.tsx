import { Link } from '@inertiajs/react';

import ProductImage from '@/Components/ProductImage';
import type { CategoryTileData } from '@/types';

/** Responsive grid used wherever category tiles are listed (home, category pages). */
export const categoryGridClasses = 'grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4 2xl:grid-cols-6';

export default function CategoryTile({ category }: { category: CategoryTileData }) {
    return (
        <Link
            href={`/${category.slug}`}
            prefetch
            className="group flex flex-col items-center gap-3 rounded-2xl border border-line bg-card p-4 text-center shadow-sm transition duration-200 hover:-translate-y-1 hover:border-accent hover:shadow-xl"
        >
            <div className="aspect-square w-full overflow-hidden rounded-xl transition duration-300 group-hover:scale-105">
                <ProductImage src={category.image} alt={category.name} placeholderIcon={category.icon} />
            </div>
            <span className="leading-snug font-semibold text-ink group-hover:text-primary">{category.name}</span>
        </Link>
    );
}
