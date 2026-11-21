import { Link, usePage } from '@inertiajs/react';
import { useEffect, useMemo, useState } from 'react';

import { ChevronDownIcon } from '@/Components/Icons';
import type { NavCategory } from '@/types';

/** Ids of the category with this slug and all its ancestors. */
function lineageIds(slug: string, nodes: NavCategory[]): number[] {
    const byId = new Map(nodes.map((node) => [node.id, node]));
    let node = nodes.find((candidate) => candidate.slug === slug);
    const ids: number[] = [];

    while (node) {
        ids.push(node.id);
        node = node.parent_id !== null ? byId.get(node.parent_id) : undefined;
    }

    return ids;
}

/**
 * Chaldal-style collapsible category tree. The branch of the current page opens automatically.
 */
export default function CategorySidebar({ onNavigate }: { onNavigate?: () => void }) {
    const { props, url } = usePage();
    const navigation = props.navigation;
    const currentSlug = url.split('?')[0].replace(/^\//, '');

    const childrenOf = useMemo(() => {
        const map = new Map<number | null, NavCategory[]>();

        for (const node of navigation) {
            map.set(node.parent_id, [...(map.get(node.parent_id) ?? []), node]);
        }

        return map;
    }, [navigation]);

    const activeIds = useMemo(() => new Set(lineageIds(currentSlug, navigation)), [currentSlug, navigation]);
    const [expanded, setExpanded] = useState<Set<number>>(() => new Set(activeIds));

    useEffect(() => {
        setExpanded((current) => new Set([...current, ...activeIds]));
    }, [activeIds]);

    const toggle = (id: number) =>
        setExpanded((current) => {
            const next = new Set(current);

            if (next.has(id)) {
                next.delete(id);
            } else {
                next.add(id);
            }

            return next;
        });

    const renderLevel = (parentId: number | null, depth: number) => (
        <ul className={depth > 0 ? 'mt-0.5 ml-4 border-l border-line pl-2' : 'space-y-0.5'}>
            {(childrenOf.get(parentId) ?? []).map((node) => {
                const hasChildren = !node.is_leaf && (childrenOf.get(node.id)?.length ?? 0) > 0;
                const isOpen = expanded.has(node.id);
                const isCurrent = node.slug === currentSlug;
                const isInPath = activeIds.has(node.id);

                return (
                    <li key={node.id}>
                        <div
                            className={`group flex items-center rounded-xl transition ${
                                isCurrent ? 'bg-primary text-primary-fg shadow-sm' : isInPath ? 'bg-primary/10 text-primary' : 'text-ink hover:bg-primary/5'
                            }`}
                        >
                            <Link
                                href={`/${node.slug}`}
                                prefetch
                                onClick={() => {
                                    if (hasChildren && !isOpen) {
                                        toggle(node.id);
                                    }
                                    onNavigate?.();
                                }}
                                className={`flex min-w-0 flex-1 items-center gap-2 px-2.5 py-2 sm:gap-2.5 sm:px-3 ${depth === 0 ? 'font-semibold' : 'text-[0.95em]'}`}
                            >
                                {depth === 0 && <span className="text-base sm:text-lg">{node.icon ?? '•'}</span>}
                                <span className="truncate">{node.name}</span>
                            </Link>
                            {hasChildren && (
                                <button
                                    type="button"
                                    aria-label={isOpen ? 'বন্ধ করুন' : 'খুলুন'}
                                    aria-expanded={isOpen}
                                    onClick={() => toggle(node.id)}
                                    className="grid size-8 shrink-0 place-items-center rounded-lg opacity-70 hover:opacity-100"
                                >
                                    <ChevronDownIcon className={`size-4 transition-transform ${isOpen ? 'rotate-180' : ''}`} />
                                </button>
                            )}
                        </div>
                        {hasChildren && isOpen && renderLevel(node.id, depth + 1)}
                    </li>
                );
            })}
        </ul>
    );

    return (
        <nav aria-label="ক্যাটাগরি">
            <p className="mb-2 px-3 text-xs font-semibold tracking-wider text-muted uppercase">ক্যাটাগরি</p>
            {renderLevel(null, 0)}
        </nav>
    );
}
