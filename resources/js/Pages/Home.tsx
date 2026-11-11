import { Head } from '@inertiajs/react';

import CategoryTile, { categoryGridClasses } from '@/Components/CategoryTile';
import type { CategoryTileData } from '@/types';

const promises = [
    { icon: '🥬', title: 'বাজারের তাজা পণ্য', text: 'প্রতিদিন বাজার থেকে বাছাই করে কেনা' },
    { icon: '🚚', title: 'দরজায় পৌঁছে দিই', text: 'আপনার এলাকায় দ্রুত ডেলিভারি' },
    { icon: '💵', title: 'ক্যাশ অন ডেলিভারি', text: 'পণ্য হাতে পেয়ে টাকা দিন' },
];

export default function Home({ categories }: { categories: CategoryTileData[] }) {
    return (
        <>
            <Head title="হোম" />

            <section className="relative overflow-hidden rounded-3xl bg-primary px-6 py-12 text-primary-fg shadow-xl sm:px-12 sm:py-16">
                <div className="absolute -top-20 -right-16 size-72 rounded-full bg-accent/25 blur-3xl" />
                <div className="absolute -bottom-24 left-1/3 size-64 rounded-full bg-accent/15 blur-3xl" />
                <div className="absolute top-1/2 right-10 hidden -translate-y-1/2 text-[9rem] leading-none opacity-90 drop-shadow-2xl md:block">🧺</div>

                <div className="relative max-w-2xl">
                    <p className="mb-4 inline-flex items-center gap-2 rounded-full bg-accent px-4 py-1 text-sm font-semibold text-accent-fg shadow">
                        ✦ ঘরে বসেই বাজার
                    </p>
                    <h1 className="text-3xl leading-snug font-bold sm:text-5xl sm:leading-tight">
                        বাজারের তাজা পণ্য, <span className="text-accent">দরজায় পৌঁছে দিই</span>
                    </h1>
                    <p className="mt-4 text-lg opacity-90">অর্ডার করুন আজই — পণ্য হাতে পেয়ে টাকা দিন।</p>
                </div>
            </section>

            <section className="mt-6 grid gap-4 sm:grid-cols-3">
                {promises.map((item) => (
                    <div key={item.title} className="flex items-center gap-4 rounded-2xl border border-line bg-card p-5 shadow-sm">
                        <span className="grid size-14 shrink-0 place-items-center rounded-2xl bg-accent/15 text-3xl">{item.icon}</span>
                        <div>
                            <h2 className="font-semibold text-primary">{item.title}</h2>
                            <p className="text-sm text-muted">{item.text}</p>
                        </div>
                    </div>
                ))}
            </section>

            {categories.length > 0 && (
                <section className="mt-10">
                    <div className="mb-5 flex items-end justify-between">
                        <div>
                            <h2 className="text-2xl font-bold text-ink">জনপ্রিয় ক্যাটাগরি</h2>
                            <div className="mt-2 h-1 w-16 rounded-full bg-accent" />
                        </div>
                    </div>
                    <div className={categoryGridClasses}>
                        {categories.map((category) => (
                            <CategoryTile key={category.id} category={category} />
                        ))}
                    </div>
                </section>
            )}
        </>
    );
}
