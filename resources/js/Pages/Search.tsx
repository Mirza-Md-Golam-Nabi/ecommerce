import { Head } from '@inertiajs/react';

import ProductGrid from '@/Components/ProductGrid';
import type { ProductCard, ScrollData } from '@/types';

export default function Search({ query, products }: { query: string; products: ScrollData<ProductCard> | null }) {
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

            {products && products.data.length > 0 && <ProductGrid data="products" products={products.data} />}

            {(!products || products.data.length === 0) && (
                <div className="rounded-2xl border border-dashed border-line bg-card py-16 text-center">
                    <span className="text-5xl">🔍</span>
                    <p className="mt-3 text-lg font-semibold text-ink">{query ? 'কোনো পণ্য পাওয়া যায়নি' : 'উপরের সার্চ বক্সে পণ্যের নাম লিখুন'}</p>
                    {query && <p className="mt-1 text-muted">অন্য নামে খুঁজে দেখুন — বাংলা বা ইংরেজি, দুটোতেই খোঁজা যায়।</p>}
                </div>
            )}
        </>
    );
}
