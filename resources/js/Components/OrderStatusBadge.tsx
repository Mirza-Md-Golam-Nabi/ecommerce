import type { OrderStatusData } from '@/types';

const tones: Record<OrderStatusData['value'], string> = {
    pending: 'bg-accent/20 text-ink',
    confirmed: 'bg-primary/10 text-primary',
    purchasing: 'bg-primary/10 text-primary',
    out_for_delivery: 'bg-primary text-primary-fg',
    delivered: 'bg-success/15 text-success',
    cancelled: 'bg-danger/10 text-danger',
};

export default function OrderStatusBadge({ status }: { status: OrderStatusData }) {
    return <span className={`inline-flex rounded-full px-3 py-1 text-sm font-semibold whitespace-nowrap ${tones[status.value]}`}>{status.label}</span>;
}
