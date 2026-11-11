import { BagIcon } from '@/Components/Icons';
import { useBag } from '@/contexts/BagContext';
import { formatMoney, toBanglaDigits } from '@/lib/format';

/**
 * Chaldal-style floating bag button on the right edge (desktop) and a bottom bar (mobile).
 */
export default function BagButton() {
    const bag = useBag();

    return (
        <>
            <button
                type="button"
                onClick={bag.open}
                className="fixed top-1/2 right-0 z-30 hidden -translate-y-1/2 overflow-hidden rounded-l-2xl shadow-2xl transition hover:-translate-x-1 lg:block"
            >
                <span className="flex flex-col items-center gap-1 bg-primary px-4 py-3 text-primary-fg">
                    <BagIcon className="size-7" />
                    <span className="text-sm font-semibold">{toBanglaDigits(bag.count)}টি পণ্য</span>
                </span>
                <span className="block bg-accent px-4 py-2 text-center text-sm font-bold text-accent-fg">{formatMoney(bag.subtotal)}</span>
            </button>

            {bag.count > 0 && (
                <div className="fixed inset-x-0 bottom-0 z-30 p-3 lg:hidden">
                    <button
                        type="button"
                        onClick={bag.open}
                        className="flex w-full items-center justify-between rounded-2xl bg-primary px-5 py-3.5 text-primary-fg shadow-2xl"
                    >
                        <span className="flex items-center gap-2 font-semibold">
                            <BagIcon className="size-6" />
                            {toBanglaDigits(bag.count)}টি পণ্য
                        </span>
                        <span className="rounded-xl bg-accent px-3 py-1 font-bold text-accent-fg">{formatMoney(bag.subtotal)}</span>
                    </button>
                </div>
            )}
        </>
    );
}
