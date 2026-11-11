import { Head, Link } from '@inertiajs/react';

import Breadcrumbs from '@/Components/Breadcrumbs';
import CategoryTile, { categoryGridClasses } from '@/Components/CategoryTile';
import EmptyState from '@/Components/EmptyState';
import ProductGrid from '@/Components/ProductGrid';
import type { Breadcrumb, CategoryTileData, ProductCard, ScrollData } from '@/types';

interface CategoryPageProps {
    category: { id: number; name: string; slug: string; icon: string | null };
    breadcrumbs: Breadcrumb[];
    subcategories: CategoryTileData[];
    products: ScrollData<ProductCard> | null;
    sort: string | null;
    /** value => label from App\Models\Product::SORT_OPTIONS (first = default). */
    sortOptions: Record<string, string> | [];
}

/**
 * A category with sub-categories shows tiles; a leaf category shows its products (Chaldal model).
 */
export default function Category({ category, breadcrumbs, subcategories, products, sort, sortOptions }: CategoryPageProps) {
    const options = Object.entries(sortOptions);
    const defaultSort = options[0]?.[0];

    return (
        <>
            <Head title={category.name} />
            <Breadcrumbs items={breadcrumbs} />

            <div className="mb-6 flex flex-wrap items-center justify-between gap-4">
                <h1 className="flex items-center gap-3 text-2xl font-bold text-ink sm:text-3xl">
                    {category.icon && <span className="grid size-12 place-items-center rounded-2xl bg-accent/15 text-2xl">{category.icon}</span>}
                    {category.name}
                </h1>

                {products && (
                    <div className="flex flex-wrap gap-2" role="group" aria-label="সাজান">
                        {options.map(([value, label]) => (
                            <Link
                                key={value}
                                href={`/${category.slug}`}
                                data={value === defaultSort ? {} : { sort: value }}
                                preserveScroll={false}
                                replace
                                className={`rounded-full border px-4 py-1.5 text-sm font-medium transition ${
                                    sort === value
                                        ? 'border-primary bg-primary text-primary-fg shadow'
                                        : 'border-line bg-card text-ink hover:border-primary hover:text-primary'
                                }`}
                            >
                                {label}
                            </Link>
                        ))}
                    </div>
                )}
            </div>

            {subcategories.length > 0 && (
                <div className={categoryGridClasses}>
                    {subcategories.map((subcategory) => (
                        <CategoryTile key={subcategory.id} category={subcategory} />
                    ))}
                </div>
            )}

            {products &&
                (products.data.length > 0 ? (
                    <ProductGrid data="products" products={products.data} />
                ) : (
                    <EmptyState icon="📦" title="এই ক্যাটাগরিতে এখনো কোনো পণ্য নেই" />
                ))}
        </>
    );
}
