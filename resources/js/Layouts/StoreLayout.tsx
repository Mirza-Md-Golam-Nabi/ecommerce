import { router } from '@inertiajs/react';
import { useEffect, useState, type ReactNode } from 'react';

import BagButton from '@/Components/BagButton';
import BagPanel from '@/Components/BagPanel';
import CategorySidebar from '@/Components/CategorySidebar';
import Header from '@/Components/Header';
import { CloseIcon } from '@/Components/Icons';
import QuickViewModal from '@/Components/QuickViewModal';
import { BagProvider } from '@/contexts/BagContext';
import { QuickViewProvider } from '@/contexts/QuickViewContext';
import { ToastProvider } from '@/contexts/ToastContext';

/**
 * Persistent storefront layout (set as the default layout in app.tsx), so header, sidebar,
 * bag state and panels survive page navigation.
 */
export default function StoreLayout({ children }: { children: ReactNode }) {
    const [menuOpen, setMenuOpen] = useState(false);

    useEffect(() => router.on('navigate', () => setMenuOpen(false)), []);

    return (
        <ToastProvider>
            <BagProvider>
                <QuickViewProvider>
                    <div className="min-h-screen bg-surface">
                        <Header onOpenMenu={() => setMenuOpen(true)} />

                        {/* Desktop: sidebar flush to the left edge, from right under the header (h-16) to the bottom of the screen. */}
                        <div className="flex">
                            <aside className="sticky top-16 hidden h-[calc(100vh-4rem)] w-64 shrink-0 overflow-y-auto border-r border-line bg-card px-2 py-3 lg:block">
                                <CategorySidebar />
                            </aside>
                            <main className="min-w-0 flex-1 px-3 pt-3 pb-24 sm:px-4 sm:pt-5 lg:px-6 lg:pr-24 lg:pb-10">
                                <div className="mx-auto max-w-[1400px]">{children}</div>
                            </main>
                        </div>

                        <div className={`fixed inset-0 z-50 lg:hidden ${menuOpen ? '' : 'pointer-events-none'}`} aria-hidden={!menuOpen}>
                            <div
                                onClick={() => setMenuOpen(false)}
                                className={`absolute inset-0 bg-ink/50 transition-opacity ${menuOpen ? 'opacity-100' : 'opacity-0'}`}
                            />
                            <div
                                className={`absolute top-0 left-0 h-full w-72 max-w-[85vw] overflow-y-auto bg-card p-3 text-sm shadow-2xl sm:w-80 sm:p-4 sm:text-base transition-transform duration-300 ${
                                    menuOpen ? 'translate-x-0' : '-translate-x-full'
                                }`}
                            >
                                <div className="mb-2 flex justify-end">
                                    <button
                                        type="button"
                                        onClick={() => setMenuOpen(false)}
                                        aria-label="বন্ধ করুন"
                                        className="grid size-9 place-items-center rounded-full hover:bg-line"
                                    >
                                        <CloseIcon className="size-5" />
                                    </button>
                                </div>
                                <CategorySidebar onNavigate={() => setMenuOpen(false)} />
                            </div>
                        </div>

                        <BagButton />
                        <BagPanel />
                        <QuickViewModal />
                    </div>
                </QuickViewProvider>
            </BagProvider>
        </ToastProvider>
    );
}
