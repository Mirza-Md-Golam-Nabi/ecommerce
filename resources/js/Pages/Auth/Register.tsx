import { Head, Link, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';

import { Field, inputClasses, PrimaryButton } from '@/Components/Form';

export default function Register() {
    const form = useForm({ name: '', phone: '', password: '', password_confirmation: '' });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.post('/register', { onFinish: () => form.reset('password', 'password_confirmation') });
    };

    return (
        <>
            <Head title="অ্যাকাউন্ট খুলুন" />

            <div className="mx-auto mt-4 max-w-md rounded-3xl border border-line bg-card p-6 shadow-sm sm:p-8">
                <div className="mb-6 text-center">
                    <span className="mx-auto grid size-14 place-items-center rounded-2xl bg-accent/15 text-3xl">🧺</span>
                    <h1 className="mt-3 text-2xl font-bold text-ink">নতুন অ্যাকাউন্ট</h1>
                    <p className="mt-1 text-muted">মাত্র এক মিনিটে অ্যাকাউন্ট খুলে অর্ডার করুন</p>
                </div>

                <form onSubmit={submit} className="space-y-4">
                    <Field label="আপনার নাম" htmlFor="name" error={form.errors.name}>
                        <input
                            id="name"
                            autoComplete="name"
                            value={form.data.name}
                            onChange={(event) => form.setData('name', event.target.value)}
                            className={inputClasses}
                            autoFocus
                        />
                    </Field>
                    <Field label="মোবাইল নম্বর" htmlFor="phone" error={form.errors.phone} hint="এই নম্বরে ফোন করে অর্ডার নিশ্চিত করা হবে।">
                        <input
                            id="phone"
                            type="tel"
                            inputMode="tel"
                            autoComplete="tel"
                            placeholder="01XXXXXXXXX"
                            value={form.data.phone}
                            onChange={(event) => form.setData('phone', event.target.value)}
                            className={inputClasses}
                        />
                    </Field>
                    <Field label="পাসওয়ার্ড" htmlFor="password" error={form.errors.password} hint="কমপক্ষে ৬ অক্ষর">
                        <input
                            id="password"
                            type="password"
                            autoComplete="new-password"
                            value={form.data.password}
                            onChange={(event) => form.setData('password', event.target.value)}
                            className={inputClasses}
                        />
                    </Field>
                    <Field label="আবার পাসওয়ার্ড" htmlFor="password_confirmation" error={form.errors.password_confirmation}>
                        <input
                            id="password_confirmation"
                            type="password"
                            autoComplete="new-password"
                            value={form.data.password_confirmation}
                            onChange={(event) => form.setData('password_confirmation', event.target.value)}
                            className={inputClasses}
                        />
                    </Field>
                    <PrimaryButton disabled={form.processing}>অ্যাকাউন্ট খুলুন</PrimaryButton>
                </form>

                <p className="mt-6 text-center text-muted">
                    আগেই অ্যাকাউন্ট আছে?{' '}
                    <Link href="/login" className="font-semibold text-primary hover:underline">
                        লগইন করুন
                    </Link>
                </p>
            </div>
        </>
    );
}
