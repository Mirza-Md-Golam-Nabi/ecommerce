import { router, usePage } from '@inertiajs/react';
import { useEffect, useState, type FormEvent } from 'react';

import { SearchIcon } from '@/Components/Icons';

export default function SearchBox({ className = '' }: { className?: string }) {
    const page = usePage();
    const currentQuery = page.component === 'Search' ? String(page.props.query ?? '') : '';
    const [term, setTerm] = useState(currentQuery);

    useEffect(() => setTerm(currentQuery), [currentQuery]);

    const submit = (event: FormEvent) => {
        event.preventDefault();

        if (term.trim() !== '') {
            router.get('/search', { q: term.trim() });
        }
    };

    return (
        <form onSubmit={submit} role="search" className={`relative ${className}`}>
            <input
                type="search"
                value={term}
                onChange={(event) => setTerm(event.target.value)}
                placeholder="পণ্য খুঁজুন… যেমন: চাল, ডিম, onion"
                aria-label="পণ্য খুঁজুন"
                className="h-11 w-full rounded-full border-0 bg-card pr-14 pl-5 text-ink shadow-inner ring-2 ring-transparent outline-none placeholder:text-muted focus:ring-accent"
            />
            <button
                type="submit"
                aria-label="খুঁজুন"
                className="absolute top-1 right-1 grid size-9 place-items-center rounded-full bg-accent text-accent-fg transition hover:brightness-105"
            >
                <SearchIcon className="size-5" />
            </button>
        </form>
    );
}
