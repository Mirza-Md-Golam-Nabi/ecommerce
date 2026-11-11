import '../css/app.css';

import { createInertiaApp, type ResolvedComponent } from '@inertiajs/react';
import { createRoot } from 'react-dom/client';

const pages = import.meta.glob<{ default: ResolvedComponent }>('./Pages/**/*.tsx');

createInertiaApp({
    title: (title) => title,
    resolve: async (name) => {
        const page = pages[`./Pages/${name}.tsx`];

        if (!page) {
            throw new Error(`Inertia page not found: ${name}`);
        }

        return (await page()).default;
    },
    setup({ el, App, props }) {
        if (el) {
            createRoot(el).render(<App {...props} />);
        }
    },
    progress: {
        color: 'var(--c-accent)',
    },
});
