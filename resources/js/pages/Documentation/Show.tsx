import { Head, Link } from '@inertiajs/react';
import PublicLayout from '@/layouts/PublicLayout';
import { formaterDate, urlCatalogue } from '@/lib/catalogue';
import type { CartePiece } from '@/types/catalogue';

type Props = {
    document: {
        id: number;
        titre: string;
        type: string;
        type_libelle: string;
        version: string;
        auteur: string;
        date_mise_a_jour: string;
        /** HTML nettoyé côté serveur (liste blanche de balises, aucun attribut). */
        html: string;
        sommaire: { id: string; titre: string }[];
    };
    pieces: CartePiece[];
    nombrePieces: number;
};

export default function DocumentationShow({ document, pieces, nombrePieces }: Props) {
    const lienCatalogue = urlCatalogue({ q: '', selection: {}, document: document.id, tri: 'reference', page: 1 });

    return (
        <PublicLayout>
            <Head title={document.titre} />

            <nav aria-label="Fil d’Ariane" className="text-sm text-ink-muted">
                <ol className="flex flex-wrap items-center gap-1.5">
                    <li>
                        <Link href="/documentation" className="hover:text-ink">
                            Documentation
                        </Link>
                    </li>
                    <li aria-hidden="true">/</li>
                    <li>
                        <Link href={`/documentation?type=${document.type}`} className="hover:text-ink">
                            {document.type_libelle}
                        </Link>
                    </li>
                </ol>
            </nav>

            <header className="mt-4 max-w-3xl">
                <h1 className="text-2xl font-semibold tracking-tight">{document.titre}</h1>
                <dl className="mt-2 flex flex-wrap gap-x-5 gap-y-1 text-sm text-ink-muted">
                    <div className="flex gap-1.5">
                        <dt>Version</dt>
                        <dd className="text-ink">{document.version}</dd>
                    </div>
                    <div className="flex gap-1.5">
                        <dt>Auteur</dt>
                        <dd className="text-ink">{document.auteur}</dd>
                    </div>
                    <div className="flex gap-1.5">
                        <dt>Mise à jour</dt>
                        <dd className="text-ink">{formaterDate(document.date_mise_a_jour)}</dd>
                    </div>
                </dl>
            </header>

            <div className="mt-8 grid gap-10 lg:grid-cols-[minmax(0,1fr)_300px]">
                <article className="contenu-document max-w-3xl" dangerouslySetInnerHTML={{ __html: document.html }} />

                <aside className="space-y-8 lg:sticky lg:top-6 lg:self-start">
                    {document.sommaire.length > 1 && (
                        <nav aria-labelledby="titre-sommaire">
                            <h2 id="titre-sommaire" className="text-sm font-semibold">
                                Sommaire
                            </h2>
                            <ol className="mt-2 space-y-1 border-l border-line text-sm">
                                {document.sommaire.map((section) => (
                                    <li key={section.id}>
                                        <a href={`#${section.id}`} className="-ml-px block border-l border-transparent py-0.5 pl-3 text-ink-muted hover:border-ink-faint hover:text-ink">
                                            {section.titre}
                                        </a>
                                    </li>
                                ))}
                            </ol>
                        </nav>
                    )}

                    <section aria-labelledby="titre-pieces">
                        <h2 id="titre-pieces" className="text-sm font-semibold">
                            Pièces concernées <span className="font-normal text-ink-faint">({nombrePieces})</span>
                        </h2>
                        {nombrePieces === 0 ? (
                            <p className="mt-2 text-sm text-ink-muted">Aucune pièce rattachée.</p>
                        ) : (
                            <>
                                <ul className="mt-2 max-h-96 divide-y divide-line overflow-y-auto border-y border-line">
                                    {pieces.map((piece) => (
                                        <li key={piece.id}>
                                            <Link href={`/pieces/${piece.reference}`} className="block py-2 hover:bg-surface">
                                                <span className="block font-mono text-[11px] text-ink-faint">{piece.reference}</span>
                                                <span className="block text-sm leading-snug">{piece.nom}</span>
                                                <span className="block text-xs text-ink-muted">
                                                    {piece.echelle_libelle} · {piece.fabricant_nom}
                                                </span>
                                            </Link>
                                        </li>
                                    ))}
                                </ul>
                                <Link
                                    href={lienCatalogue}
                                    className="mt-3 inline-flex h-9 items-center rounded border border-line-strong px-3 text-sm font-medium hover:bg-surface"
                                >
                                    Voir {nombrePieces > 1 ? `les ${nombrePieces} pièces` : 'la pièce'} dans le catalogue
                                </Link>
                            </>
                        )}
                    </section>
                </aside>
            </div>
        </PublicLayout>
    );
}
