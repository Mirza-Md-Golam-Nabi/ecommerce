import type { ReactNode } from 'react';

interface EmptyStateProps {
    icon: string;
    title: string;
    description?: string;
    /** Optional action, e.g. a link button. */
    children?: ReactNode;
}

export default function EmptyState({ icon, title, description, children }: EmptyStateProps) {
    return (
        <div className="rounded-2xl border border-dashed border-line bg-card px-4 py-16 text-center">
            <span className="text-5xl">{icon}</span>
            <p className="mt-3 text-lg font-semibold text-ink">{title}</p>
            {description && <p className="mt-1 text-muted">{description}</p>}
            {children && <div className="mt-4">{children}</div>}
        </div>
    );
}
