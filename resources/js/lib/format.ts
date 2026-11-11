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

/** Poisha → "৳১,২৫০.৫". All money in the app is integer poisha. */
export function formatMoney(poisha: number): string {
    return `৳${banglaNumber.format(poisha / 100)}`;
}
