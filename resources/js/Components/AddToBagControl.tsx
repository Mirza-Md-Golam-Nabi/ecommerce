import { BagIcon, MinusIcon, PlusIcon } from '@/Components/Icons';
import { useBag } from '@/contexts/BagContext';
import { toBanglaDigits } from '@/lib/format';
import type { ProductCard } from '@/types';

interface AddToBagControlProps {
    product: ProductCard;
    size?: 'sm' | 'lg';
}

/**
 * "ব্যাগে যোগ করুন" button that turns into a − qty + stepper once the product is in the bag.
 */
export default function AddToBagControl({ product, size = 'sm' }: AddToBagControlProps) {
    const { quantityOf, setQuantity } = useBag();
    const quantity = quantityOf(product.id);
    const height = size === 'lg' ? 'h-12 text-base' : 'h-10 text-sm';
    const atLimit = product.max_quantity !== null && quantity >= product.max_quantity;

    if (!product.is_available) {
        return (
            <div className={`flex w-full items-center justify-center rounded-xl bg-line/60 font-medium text-muted ${height}`}>
                এখন পাওয়া যাচ্ছে না
            </div>
        );
    }

    if (quantity === 0) {
        return (
            <button
                type="button"
                onClick={() => setQuantity(product, 1)}
                className={`flex w-full items-center justify-center gap-2 rounded-xl bg-primary font-semibold text-primary-fg shadow-sm transition hover:brightness-110 active:scale-[0.98] ${height}`}
            >
                <BagIcon className="size-5" />
                ব্যাগে যোগ করুন
            </button>
        );
    }

    return (
        <div className={`flex w-full items-center justify-between rounded-xl bg-accent font-semibold text-accent-fg shadow-sm ${height}`}>
            <button
                type="button"
                aria-label="একটি কমান"
                onClick={() => setQuantity(product, quantity - 1)}
                className="grid h-full w-11 place-items-center rounded-l-xl transition hover:bg-black/10"
            >
                <MinusIcon className="size-5" />
            </button>
            <span aria-live="polite">{toBanglaDigits(quantity)}টি ব্যাগে</span>
            <button
                type="button"
                aria-label="আরও একটি"
                disabled={atLimit}
                title={atLimit ? 'সর্বোচ্চ পরিমাণ' : undefined}
                onClick={() => setQuantity(product, quantity + 1)}
                className="grid h-full w-11 place-items-center rounded-r-xl transition hover:bg-black/10 disabled:cursor-not-allowed disabled:opacity-40"
            >
                <PlusIcon className="size-5" />
            </button>
        </div>
    );
}
