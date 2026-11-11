import { Head, Link } from '@inertiajs/react';

import Breadcrumbs from '@/Components/Breadcrumbs';
import CategoryTile from '@/Components/CategoryTile';
import ProductGrid from '@/Components/ProductGrid';
import type { Breadcrumb, CategoryTileData, ProductCard, ScrollData } from '@/types';

type Sort = 'name' | 'price_asc' | 'price_desc' | 'popular';

interface CategoryPageProps {
    category: { id: number; name: string; slug: string; icon: string | null };
    breadcrumbs: Breadcrumb[];
    subcategories: CategoryTileData[];
    products: ScrollData<ProductCard> | null;
    sort: Sort | null;
}

const sortOptions: { value: Sort; label: string }[] = [
    { value: 'name', label: 'নাম অনুযায়ী' },
    { value: 'price_asc', label: 'দাম: কম → বেশি' },
    { value: 'price_desc', label: 'দাম: বেশি → কম' },
    { value: 'popular', label: 'জনপ্রিয়' },
];

/**
 * A category with sub-categories shows tiles; a leaf category shows its products (Chaldal model).
 */
export default function Category({ category, breadcrumbs, subcategories, products, sort }: CategoryPageProps) {
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
                        {sortOptions.map((option) => (
                            <Link
                                key={option.value}
                                href={`/${category.slug}`}
                                data={option.value === 'name' ? {} : { sort: option.value }}
                                preserveScroll={false}
                                replace
                                className={`rounded-full border px-4 py-1.5 text-sm font-medium transition ${
                                    sort === option.value
                                        ? 'border-primary bg-primary text-primary-fg shadow'
                                        : 'border-line bg-card text-ink hover:border-primary hover:text-primary'
                                }`}
                            >
                                {option.label}
                            </Link>
                        ))}
                    </div>
                )}
            </div>

            {subcategories.length > 0 && (
                <div className="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4 2xl:grid-cols-6">
                    {subcategories.map((subcategory) => (
                        <CategoryTile key={subcategory.id} category={subcategory} />
                    ))}
                </div>
            )}

            {products &&
                (products.data.length > 0 ? (
                    <ProductGrid data="products" products={products.data} />
                ) : (
                    <div className="rounded-2xl border border-dashed border-line bg-card py-16 text-center">
                        <span className="text-5xl">📦</span>
                        <p className="mt-3 text-muted">এই ক্যাটাগরিতে এখনো কোনো পণ্য নেই।</p>
                    </div>
                ))}
        </>
    );
}
