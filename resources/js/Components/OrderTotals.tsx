import WeightedPriceNote from '@/Components/WeightedPriceNote';
import { formatMoney } from '@/lib/format';

interface OrderTotalsProps {
    subtotal: number;
    deliveryCharge: number;
    total: number;
    /** Weighted items not yet weighed: total may still change. */
    isEstimated: boolean;
}

/**
 * Subtotal / delivery / total block used by the bag panel, checkout and order detail. Money in poisha.
 */
export default function OrderTotals({ subtotal, deliveryCharge, total, isEstimated }: OrderTotalsProps) {
    return (
        <div className="space-y-2">
            <div className="flex justify-between text-muted">
                <span>পণ্যের দাম</span>
                <span className="font-medium text-ink">{formatMoney(subtotal)}</span>
            </div>
            <div className="flex justify-between text-muted">
                <span>ডেলিভারি চার্জ</span>
                <span className="font-medium text-ink">{formatMoney(deliveryCharge)}</span>
            </div>
            <div className="flex justify-between border-t border-dashed border-line pt-2 text-lg font-bold text-ink">
                <span>মোট {isEstimated && <span className="text-sm font-medium text-muted">(আনুমানিক)</span>}</span>
                <span className="text-primary">{formatMoney(total)}</span>
            </div>
            {isEstimated && <WeightedPriceNote />}
        </div>
    );
}
