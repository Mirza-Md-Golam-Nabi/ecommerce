import { Head, Link, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';

import { Field, inputClasses, PrimaryButton } from '@/Components/Form';

export default function Login() {
    const form = useForm({ phone: '', password: '', remember: true });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.post('/login', { onFinish: () => form.reset('password') });
    };

    return (
        <>
            <Head title="লগইন" />

            <div className="mx-auto mt-4 max-w-md rounded-3xl border border-line bg-card p-6 shadow-sm sm:p-8">
                <div className="mb-6 text-center">
                    <span className="mx-auto grid size-14 place-items-center rounded-2xl bg-accent/15 text-3xl">👋</span>
                    <h1 className="mt-3 text-2xl font-bold text-ink">আবার স্বাগতম</h1>
                    <p className="mt-1 text-muted">মোবাইল নম্বর ও পাসওয়ার্ড দিয়ে লগইন করুন</p>
                </div>

                <form onSubmit={submit} className="space-y-4">
                    <Field label="মোবাইল নম্বর" htmlFor="phone" error={form.errors.phone}>
                        <input
                            id="phone"
                            type="tel"
                            inputMode="tel"
                            autoComplete="tel"
                            placeholder="01XXXXXXXXX"
                            value={form.data.phone}
                            onChange={(event) => form.setData('phone', event.target.value)}
                            className={inputClasses}
                            autoFocus
                        />
                    </Field>
                    <Field label="পাসওয়ার্ড" htmlFor="password" error={form.errors.password}>
                        <input
                            id="password"
                            type="password"
                            autoComplete="current-password"
                            value={form.data.password}
                            onChange={(event) => form.setData('password', event.target.value)}
                            className={inputClasses}
                        />
                    </Field>
                    <PrimaryButton disabled={form.processing}>লগইন করুন</PrimaryButton>
                </form>

                <p className="mt-6 text-center text-muted">
                    নতুন কাস্টমার?{' '}
                    <Link href="/register" className="font-semibold text-primary hover:underline">
                        অ্যাকাউন্ট খুলুন
                    </Link>
                </p>
            </div>
        </>
    );
}
