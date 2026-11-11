import { Head } from '@inertiajs/react';

import EmptyState from '@/Components/EmptyState';
import ProductGrid from '@/Components/ProductGrid';
import type { ProductCard, ScrollData } from '@/types';

export default function Search({ query, products }: { query: string; products: ScrollData<ProductCard> | null }) {
    const hasResults = products !== null && products.data.length > 0;

    return (
        <>
            <Head title={query ? `"${query}" খোঁজ` : 'খুঁজুন'} />

            <h1 className="mb-6 text-2xl font-bold text-ink">
                {query ? (
                    <>
                        খোঁজের ফলাফল: <span className="text-primary">“{query}”</span>
                    </>
                ) : (
                    'কী খুঁজছেন?'
                )}
            </h1>

            {hasResults ? (
                <ProductGrid data="products" products={products.data} />
            ) : query ? (
                <EmptyState icon="🔍" title="কোনো পণ্য পাওয়া যায়নি" description="অন্য নামে খুঁজে দেখুন — বাংলা বা ইংরেজি, দুটোতেই খোঁজা যায়।" />
            ) : (
                <EmptyState icon="🔍" title="উপরের সার্চ বক্সে পণ্যের নাম লিখুন" />
            )}
        </>
    );
}
