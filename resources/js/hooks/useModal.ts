import { useEffect } from 'react';

/**
 * Shared overlay behaviour (bag panel, quick view, …): lock page scroll and close on Escape while open.
 */
export function useModal(isOpen: boolean, onClose: () => void): void {
    useEffect(() => {
        if (!isOpen) {
            return;
        }

        const onKey = (event: KeyboardEvent) => {
            if (event.key === 'Escape') {
                onClose();
            }
        };

        document.body.style.overflow = 'hidden';
        window.addEventListener('keydown', onKey);

        return () => {
            document.body.style.overflow = '';
            window.removeEventListener('keydown', onKey);
        };
    }, [isOpen, onClose]);
}
