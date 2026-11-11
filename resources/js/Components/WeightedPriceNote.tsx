export const WEIGHTED_PRICE_NOTE = 'ওজনের পণ্যের চূড়ান্ত দাম ডেলিভারির সময় আসল ওজন অনুযায়ী ঠিক হবে।';

/**
 * Explains that weighted items are priced by actual weight. `callout` for product views, `hint` under totals.
 */
export default function WeightedPriceNote({ variant = 'hint' }: { variant?: 'hint' | 'callout' }) {
    if (variant === 'callout') {
        return <p className="rounded-xl bg-accent/15 px-4 py-3 text-sm text-ink">⚖️ {WEIGHTED_PRICE_NOTE}</p>;
    }

    return <p className="text-xs text-muted">{WEIGHTED_PRICE_NOTE}</p>;
}
