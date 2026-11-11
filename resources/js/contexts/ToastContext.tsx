import { usePage } from '@inertiajs/react';
import { createContext, useCallback, useContext, useEffect, useMemo, useRef, useState, type ReactNode } from 'react';

type ToastTone = 'success' | 'error';

interface Toast {
    id: number;
    message: string;
    tone: ToastTone;
}

const ToastContext = createContext<((message: string, tone?: ToastTone) => void) | null>(null);

export function ToastProvider({ children }: { children: ReactNode }) {
    const [toasts, setToasts] = useState<Toast[]>([]);
    const nextId = useRef(1);

    const show = useCallback((message: string, tone: ToastTone = 'success') => {
        const id = nextId.current++;

        setToasts((current) => [...current.slice(-2), { id, message, tone }]);
        window.setTimeout(() => setToasts((current) => current.filter((toast) => toast.id !== id)), 3500);
    }, []);

    const value = useMemo(() => show, [show]);

    /* Server flash messages (session 'success' / 'error') appear as toasts after a visit. */
    const { flash } = usePage().props;

    useEffect(() => {
        if (flash.success) {
            show(flash.success, 'success');
        }

        if (flash.error) {
            show(flash.error, 'error');
        }
    }, [flash, show]);

    return (
        <ToastContext.Provider value={value}>
            {children}
            <div aria-live="polite" className="pointer-events-none fixed inset-x-0 bottom-24 z-[70] flex flex-col items-center gap-2 px-4 lg:bottom-8">
                {toasts.map((toast) => (
                    <div
                        key={toast.id}
                        className={`pointer-events-auto max-w-md rounded-2xl px-5 py-3 text-sm font-medium shadow-2xl ${
                            toast.tone === 'error' ? 'bg-danger text-white' : 'bg-primary text-primary-fg'
                        }`}
                    >
                        {toast.message}
                    </div>
                ))}
            </div>
        </ToastContext.Provider>
    );
}

export function useToast() {
    const context = useContext(ToastContext);

    if (!context) {
        throw new Error('useToast must be used inside ToastProvider');
    }

    return context;
}
