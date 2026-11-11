import { Head, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';

import { ErrorBanner, Field, inputClasses, PrimaryButton } from '@/Components/Form';
import ProductImage from '@/Components/ProductImage';
import { formatMoney, toBanglaDigits } from '@/lib/format';
import type { Area, BagDetails } from '@/types';

const OTHER_AREA = 'other';

interface CheckoutProps {
    summary: BagDetails;
    areas: Area[];
    address: { recipient_name: string; phone: string; area: string; area_other: string; address_line: string };
}

export default function Checkout({ summary, areas, address }: CheckoutProps) {
    const form = useForm({ ...address, customer_note: '' });
    const errors = form.errors as Record<string, string | undefined>;
    const orderableLines = summary.items.filter((line) => line.can_be_ordered);
    const hasUnavailable = orderableLines.length !== summary.items.length;

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.post('/checkout', { preserveScroll: true });
    };

    return (
        <>
            <Head title="চেকআউট" />
            <h1 className="mb-6 text-2xl font-bold text-ink sm:text-3xl">অর্ডার নিশ্চিত করুন</h1>

            <ErrorBanner message={errors.order} />

            <form onSubmit={submit} className="grid items-start gap-6 lg:grid-cols-[1fr_400px]">
                <section className="space-y-4 rounded-3xl border border-line bg-card p-5 shadow-sm sm:p-6">
                    <h2 className="flex items-center gap-2 text-lg font-bold text-ink">📍 ডেলিভারির ঠিকানা</h2>

                    <div className="grid gap-4 sm:grid-cols-2">
                        <Field label="প্রাপকের নাম" htmlFor="recipient_name" error={form.errors.recipient_name}>
                            <input
                                id="recipient_name"
                                value={form.data.recipient_name}
                                onChange={(event) => form.setData('recipient_name', event.target.value)}
                                className={inputClasses}
                            />
                        </Field>
                        <Field label="মোবাইল নম্বর" htmlFor="phone" error={form.errors.phone}>
                            <input
                                id="phone"
                                type="tel"
                                inputMode="tel"
                                value={form.data.phone}
                                onChange={(event) => form.setData('phone', event.target.value)}
                                className={inputClasses}
                            />
                        </Field>
                    </div>

                    <Field label="এলাকা" htmlFor="area" error={form.errors.area}>
                        <select id="area" value={form.data.area} onChange={(event) => form.setData('area', event.target.value)} className={inputClasses}>
                            <option value="" disabled>
                                এলাকা বাছাই করুন
                            </option>
                            {areas.map((area) => (
                                <option key={area.id} value={String(area.id)}>
                                    {area.name}
                                </option>
                            ))}
                            <option value={OTHER_AREA}>অন্যান্য</option>
                        </select>
                    </Field>

                    {form.data.area === OTHER_AREA && (
                        <Field label="আপনার এলাকার নাম" htmlFor="area_other" error={form.errors.area_other}>
                            <input
                                id="area_other"
                                value={form.data.area_other}
                                onChange={(event) => form.setData('area_other', event.target.value)}
                                className={inputClasses}
                                autoFocus
                            />
                        </Field>
                    )}

                    <Field label="বাসা / রোড / ল্যান্ডমার্ক" htmlFor="address_line" error={form.errors.address_line}>
                        <textarea
                            id="address_line"
                            rows={3}
                            value={form.data.address_line}
                            onChange={(event) => form.setData('address_line', event.target.value)}
                            className={`${inputClasses} h-auto py-3`}
                            placeholder="যেমন: বাড়ি ১২, রোড ৫, মসজিদের পাশে"
                        />
                    </Field>

                    <Field label="ডেলিভারির জন্য নোট (ঐচ্ছিক)" htmlFor="customer_note" error={form.errors.customer_note}>
                        <input
                            id="customer_note"
                            value={form.data.customer_note}
                            onChange={(event) => form.setData('customer_note', event.target.value)}
                            className={inputClasses}
                            placeholder="যেমন: সন্ধ্যার পর দিবেন"
                        />
                    </Field>
                </section>

                <aside className="space-y-4 rounded-3xl border border-line bg-card p-5 shadow-sm sm:p-6 lg:sticky lg:top-20">
                    <h2 className="text-lg font-bold text-ink">🛍️ আপনার অর্ডার ({toBanglaDigits(summary.count)}টি পণ্য)</h2>

                    <ul className="max-h-80 space-y-3 overflow-y-auto pr-1">
                        {summary.items.map((line) => (
                            <li key={line.product.id} className={`flex items-center gap-3 ${line.can_be_ordered ? '' : 'opacity-50'}`}>
                                <div className="size-12 shrink-0 overflow-hidden rounded-lg">
                                    <ProductImage src={line.product.image} alt={line.product.name} placeholderIcon={line.product.placeholder_icon} />
                                </div>
                                <div className="min-w-0 flex-1">
                                    <p className="line-clamp-1 font-medium text-ink">{line.product.name}</p>
                                    <p className="text-sm text-muted">
                                        {line.product.unit_label} × {toBanglaDigits(line.quantity)}
                                    </p>
                                </div>
                                <span className="font-semibold text-ink">{line.can_be_ordered ? formatMoney(line.line_total) : 'নেই'}</span>
                            </li>
                        ))}
                    </ul>

                    {hasUnavailable && <p className="rounded-xl bg-danger/10 px-3 py-2 text-sm text-danger">কিছু পণ্য এখন পাওয়া যাচ্ছে না — ব্যাগ থেকে সরিয়ে নিন।</p>}

                    <div className="space-y-2 border-t border-line pt-4">
                        <div className="flex justify-between text-muted">
                            <span>পণ্যের দাম</span>
                            <span className="text-ink">{formatMoney(summary.subtotal)}</span>
                        </div>
                        <div className="flex justify-between text-muted">
                            <span>ডেলিভারি চার্জ</span>
                            <span className="text-ink">{formatMoney(summary.delivery_charge)}</span>
                        </div>
                        <div className="flex justify-between border-t border-dashed border-line pt-2 text-xl font-bold">
                            <span>মোট {summary.has_weighted && <span className="text-sm font-medium text-muted">(আনুমানিক)</span>}</span>
                            <span className="text-primary">{formatMoney(summary.total)}</span>
                        </div>
                        {summary.has_weighted && <p className="text-xs text-muted">ওজনের পণ্যের চূড়ান্ত দাম ডেলিভারির সময় আসল ওজন অনুযায়ী ঠিক হবে।</p>}
                    </div>

                    <div className="rounded-xl bg-accent/15 px-4 py-3 text-sm text-ink">💵 ক্যাশ অন ডেলিভারি — পণ্য হাতে পেয়ে টাকা দিন। অর্ডারের পর আমরা ফোন করে নিশ্চিত করব।</div>

                    <PrimaryButton disabled={form.processing || hasUnavailable}>অর্ডার নিশ্চিত করুন</PrimaryButton>
                </aside>
            </form>
        </>
    );
}
