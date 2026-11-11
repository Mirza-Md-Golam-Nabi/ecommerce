import { Head } from '@inertiajs/react';

import StoreLayout from '@/Layouts/StoreLayout';

const promises = [
    { icon: '🥬', title: 'বাজারের তাজা পণ্য', text: 'প্রতিদিন বাজার থেকে বাছাই করে কেনা' },
    { icon: '🚚', title: 'দরজায় পৌঁছে দিই', text: 'আপনার এলাকায় দ্রুত ডেলিভারি' },
    { icon: '💵', title: 'ক্যাশ অন ডেলিভারি', text: 'পণ্য হাতে পেয়ে টাকা দিন' },
];

export default function Home() {
    return (
        <StoreLayout>
            <Head title="হোম" />

            <section className="relative overflow-hidden rounded-3xl bg-primary px-6 py-14 text-primary-fg shadow-xl sm:px-12">
                <div className="absolute -top-16 -right-16 size-64 rounded-full bg-accent/20 blur-2xl" />
                <div className="absolute -bottom-20 -left-10 size-56 rounded-full bg-accent/10 blur-2xl" />

                <div className="relative max-w-2xl">
                    <p className="mb-3 inline-block rounded-full bg-accent px-4 py-1 text-sm font-semibold text-accent-fg">
                        শীঘ্রই আসছে
                    </p>
                    <h1 className="text-3xl leading-snug font-bold sm:text-5xl sm:leading-tight">
                        বাজারের তাজা পণ্য, <span className="text-accent">দরজায় পৌঁছে দিই</span>
                    </h1>
                    <p className="mt-4 text-lg opacity-90">ঘরে বসেই অর্ডার করুন, পণ্য হাতে পেয়ে টাকা দিন।</p>
                </div>
            </section>

            <section className="mt-10 grid gap-5 sm:grid-cols-3">
                {promises.map((item) => (
                    <div
                        key={item.title}
                        className="rounded-2xl border border-line bg-card p-6 shadow-sm transition hover:-translate-y-1 hover:shadow-lg"
                    >
                        <div className="mb-3 text-4xl">{item.icon}</div>
                        <h2 className="text-lg font-semibold text-primary">{item.title}</h2>
                        <p className="mt-1 text-muted">{item.text}</p>
                    </div>
                ))}
            </section>
        </StoreLayout>
    );
}
