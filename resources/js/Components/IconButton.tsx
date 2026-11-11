import type { ButtonHTMLAttributes, ReactNode } from 'react';

interface IconButtonProps extends Omit<ButtonHTMLAttributes<HTMLButtonElement>, 'children'> {
    /** Accessible label and tooltip, e.g. "মুছুন". Required: icon-only buttons must still be understandable. */
    label: string;
    icon: ReactNode;
    tone?: 'default' | 'danger';
}

/**
 * Icon-only button (docs/07-conventions.md rule 16: edit & delete are icon-only, with tooltip).
 */
export default function IconButton({ label, icon, tone = 'default', className = '', ...props }: IconButtonProps) {
    const toneClasses = tone === 'danger' ? 'text-muted hover:bg-danger/10 hover:text-danger' : 'text-muted hover:bg-primary/10 hover:text-primary';

    return (
        <button
            type="button"
            aria-label={label}
            title={label}
            className={`grid size-9 place-items-center rounded-full transition ${toneClasses} ${className}`}
            {...props}
        >
            {icon}
        </button>
    );
}
