const banglaNumber = new Intl.NumberFormat('bn-BD', { maximumFractionDigits: 2 });

const BANGLA_DIGITS = '০১২৩৪৫৬৭৮৯';

/** "12" → "১২" */
export function toBanglaDigits(value: number | string): string {
    return String(value).replace(/\d/g, (digit) => BANGLA_DIGITS[Number(digit)]);
}

/** Bangla-formatted number with grouping: 1250 → "১,২৫০". */
export function formatNumber(value: number): string {
    return banglaNumber.format(value);
}

const banglaDateTime = new Intl.DateTimeFormat('bn-BD', { day: 'numeric', month: 'long', year: 'numeric', hour: 'numeric', minute: '2-digit' });

/** ISO date → "১১ নভেম্বর, ২০২৬ ৪:৩০ PM" style Bangla date-time. */
export function formatDateTime(iso: string): string {
    return banglaDateTime.format(new Date(iso));
}

/** Grams → "২.০৮ কেজি" / "৭৫০ গ্রাম". */
export function formatWeight(grams: number): string {
    return grams >= 1000 ? `${formatNumber(grams / 1000)} কেজি` : `${formatNumber(grams)} গ্রাম`;
}

/** Poisha → "৳১,২৫০.৫". All money in the app is integer poisha. */
export function formatMoney(poisha: number): string {
    return `৳${banglaNumber.format(poisha / 100)}`;
}
