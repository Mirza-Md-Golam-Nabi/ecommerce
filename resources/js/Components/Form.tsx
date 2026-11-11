import type { ReactNode } from 'react';

export const inputClasses =
    'h-12 w-full rounded-xl border border-line bg-card px-4 text-ink shadow-sm outline-none transition placeholder:text-muted focus:border-primary focus:ring-2 focus:ring-primary/20';

interface FieldProps {
    label: string;
    htmlFor: string;
    error?: string;
    hint?: string;
    children: ReactNode;
}

export function Field({ label, htmlFor, error, hint, children }: FieldProps) {
    return (
        <div>
            <label htmlFor={htmlFor} className="mb-1.5 block text-sm font-semibold text-ink">
                {label}
            </label>
            {children}
            {error ? <p className="mt-1.5 text-sm text-danger">{error}</p> : hint ? <p className="mt-1.5 text-xs text-muted">{hint}</p> : null}
        </div>
    );
}

export function PrimaryButton({ children, disabled, className = '' }: { children: ReactNode; disabled?: boolean; className?: string }) {
    return (
        <button
            type="submit"
            disabled={disabled}
            className={`flex h-12 w-full items-center justify-center rounded-xl bg-primary font-bold text-primary-fg shadow transition hover:brightness-110 disabled:cursor-wait disabled:opacity-60 ${className}`}
        >
            {children}
        </button>
    );
}

export function ErrorBanner({ message }: { message?: string }) {
    if (!message) {
        return null;
    }

    return <div className="mb-5 rounded-2xl border border-danger/30 bg-danger/10 px-4 py-3 text-danger">{message}</div>;
}
