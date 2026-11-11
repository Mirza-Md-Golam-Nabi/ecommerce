import { toBanglaDigits } from '@/lib/format';

/**
 * "-15%" badge pinned to the top-left of a product image.
 */
export default function DiscountBadge({ percent, size = 'sm' }: { percent: number | null; size?: 'sm' | 'lg' }) {
    if (!percent) {
        return null;
    }

    return (
        <span
            className={`absolute top-3 left-3 z-10 rounded-full bg-accent font-bold text-accent-fg shadow ${size === 'lg' ? 'px-3 py-1' : 'px-2.5 py-0.5 text-xs'}`}
        >
            -{toBanglaDigits(percent)}%
        </span>
    );
}
