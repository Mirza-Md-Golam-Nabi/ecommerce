import { formatMoney } from '@/lib/format';

interface PriceProps {
    price: number;
    compareAtPrice?: number | null;
    size?: 'sm' | 'lg';
}

export default function Price({ price, compareAtPrice, size = 'sm' }: PriceProps) {
    return (
        <div className="flex flex-wrap items-baseline gap-x-2">
            <span className={`font-bold text-primary ${size === 'lg' ? 'text-3xl' : 'text-lg'}`}>{formatMoney(price)}</span>
            {compareAtPrice ? (
                <span className={`text-muted line-through ${size === 'lg' ? 'text-lg' : 'text-sm'}`}>{formatMoney(compareAtPrice)}</span>
            ) : null}
        </div>
    );
}
