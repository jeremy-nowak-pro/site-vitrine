import { Link } from '@inertiajs/react';

type Props = {
    page: number;
    pages: number;
    url: (page: number) => string;
};

/**
 * Pages affichées : la première, la dernière et deux voisines de la page courante.
 */
function pagesVisibles(page: number, pages: number): (number | 'ellipse')[] {
    const retenues = new Set([1, pages, page - 2, page - 1, page, page + 1, page + 2]);
    const triees = [...retenues].filter((p) => p >= 1 && p <= pages).sort((a, b) => a - b);

    return triees.flatMap((p, i) => (i > 0 && p - triees[i - 1] > 1 ? ['ellipse' as const, p] : [p]));
}

const base = 'inline-flex h-9 min-w-9 items-center justify-center rounded border px-2.5 text-sm';

export default function Pagination({ page, pages, url }: Props) {
    if (pages <= 1) {
        return null;
    }

    return (
        <nav aria-label="Pagination" className="flex flex-wrap items-center justify-center gap-1.5">
            {page > 1 ? (
                <Link href={url(page - 1)} rel="prev" className={`${base} border-line hover:bg-surface`}>
                    Précédente
                </Link>
            ) : (
                <span className={`${base} border-line text-ink-faint`} aria-hidden="true">
                    Précédente
                </span>
            )}

            <ul className="flex flex-wrap items-center gap-1.5">
                {pagesVisibles(page, pages).map((p, i) =>
                    p === 'ellipse' ? (
                        <li key={`e${i}`} className="px-1 text-ink-faint" aria-hidden="true">
                            …
                        </li>
                    ) : (
                        <li key={p}>
                            <Link
                                href={url(p)}
                                aria-current={p === page ? 'page' : undefined}
                                aria-label={`Page ${p}`}
                                className={`${base} tabular-nums ${
                                    p === page ? 'border-accent bg-accent text-white' : 'border-line hover:bg-surface'
                                }`}
                            >
                                {p}
                            </Link>
                        </li>
                    ),
                )}
            </ul>

            {page < pages ? (
                <Link href={url(page + 1)} rel="next" className={`${base} border-line hover:bg-surface`}>
                    Suivante
                </Link>
            ) : (
                <span className={`${base} border-line text-ink-faint`} aria-hidden="true">
                    Suivante
                </span>
            )}
        </nav>
    );
}
