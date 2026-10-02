import { Head, Link, router } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';
import FacetGroup from '@/components/catalogue/FacetGroup';
import PieceCard from '@/components/catalogue/PieceCard';
import Pagination from '@/components/Pagination';
import PublicLayout from '@/layouts/PublicLayout';
import { basculerValeur, formaterNombre, nombreFiltresActifs, triParDefaut, urlCatalogue } from '@/lib/catalogue';
import type { CleFacette, EtatCatalogue, ResultatsCatalogue } from '@/types/catalogue';

type Props = {
    filtres: EtatCatalogue;
    resultats: ResultatsCatalogue | null;
    document: { id: number; titre: string; slug: string } | null;
    facettes: Record<CleFacette, string>;
    tris: { valeur: string; libelle: string }[];
};

const DELAI_SAISIE = 300;

export default function CatalogueIndex({ filtres, resultats, document, tris }: Props) {
    const [saisie, setSaisie] = useState(filtres.q);
    const [chargement, setChargement] = useState(false);
    const [filtresOuverts, setFiltresOuverts] = useState(false);
    const champRecherche = useRef<HTMLInputElement>(null);

    const naviguer = (etat: EtatCatalogue, options: { remplacer?: boolean; garderDefilement?: boolean } = {}) => {
        router.get(
            urlCatalogue(etat),
            {},
            {
                preserveState: true,
                preserveScroll: options.garderDefilement ?? true,
                replace: options.remplacer ?? false,
                only: ['filtres', 'resultats', 'document'],
                onStart: () => setChargement(true),
                onFinish: () => setChargement(false),
            },
        );
    };

    // Recherche à la frappe, après une courte pause.
    useEffect(() => {
        if (saisie.trim() === filtres.q) {
            return;
        }
        const minuteur = window.setTimeout(() => {
            const q = saisie.trim();
            const tri = filtres.tri === triParDefaut(filtres.q) ? triParDefaut(q) : filtres.tri;
            naviguer({ ...filtres, q, tri, page: 1 }, { remplacer: true });
        }, DELAI_SAISIE);

        return () => window.clearTimeout(minuteur);
    }, [saisie]);

    // Navigation arrière ou avant : le champ reprend la valeur de l'URL, sauf pendant la frappe.
    useEffect(() => {
        if (champRecherche.current !== window.document.activeElement) {
            setSaisie(filtres.q);
        }
    }, [filtres.q]);

    const basculer = (facette: CleFacette, valeur: string) => naviguer(basculerValeur(filtres, facette, valeur));
    const toutEffacer = () => {
        setSaisie('');
        naviguer({ q: '', selection: {}, document: null, tri: triParDefaut(''), page: 1 });
    };

    const actifs = nombreFiltresActifs(filtres);
    const puces = (resultats?.facettes ?? []).flatMap((facette) =>
        facette.options.filter((option) => option.actif).map((option) => ({ facette, option })),
    );

    return (
        <PublicLayout>
            <Head title="Catalogue" />

            <div className="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <h1 className="text-2xl font-semibold tracking-tight">Catalogue</h1>
                    <p className="mt-1 text-sm text-ink-muted" aria-live="polite">
                        {resultats
                            ? `${formaterNombre(resultats.total)} pièce${resultats.total > 1 ? 's' : ''}`
                            : 'Recherche indisponible'}
                    </p>
                </div>

                <div className="flex flex-col gap-3 sm:flex-row sm:items-center">
                    <form
                        role="search"
                        onSubmit={(e) => {
                            e.preventDefault();
                            const q = saisie.trim();
                            naviguer({ ...filtres, q, tri: filtres.tri === triParDefaut(filtres.q) ? triParDefaut(q) : filtres.tri, page: 1 });
                        }}
                    >
                        <label htmlFor="recherche" className="sr-only">
                            Rechercher une pièce
                        </label>
                        <input
                            ref={champRecherche}
                            id="recherche"
                            type="search"
                            value={saisie}
                            onChange={(e) => setSaisie(e.target.value)}
                            placeholder="Nom, référence, modèle…"
                            autoComplete="off"
                            className="h-9 w-full rounded border border-line-strong bg-canvas px-3 text-sm placeholder:text-ink-faint sm:w-72"
                        />
                    </form>
                    <div className="flex items-center gap-2">
                        <label htmlFor="tri" className="shrink-0 text-sm text-ink-muted">
                            Trier par
                        </label>
                        <select
                            id="tri"
                            value={filtres.tri}
                            onChange={(e) => naviguer({ ...filtres, tri: e.target.value, page: 1 })}
                            className="h-9 w-full rounded border border-line-strong bg-canvas px-2 text-sm sm:w-auto"
                        >
                            {tris.map((tri) => (
                                <option key={tri.valeur} value={tri.valeur}>
                                    {tri.libelle}
                                </option>
                            ))}
                        </select>
                    </div>
                </div>
            </div>

            {(puces.length > 0 || document) && (
                <div className="mt-5 flex flex-wrap items-center gap-2">
                    {document && (
                        <button
                            type="button"
                            onClick={() => naviguer({ ...filtres, document: null, page: 1 })}
                            className="inline-flex items-center gap-1.5 rounded border border-line bg-surface px-2.5 py-1 text-xs hover:border-line-strong"
                        >
                            <span className="text-ink-muted">Document :</span> {document.titre}
                            <span aria-hidden="true" className="text-ink-faint">×</span>
                            <span className="sr-only">(retirer ce filtre)</span>
                        </button>
                    )}
                    {puces.map(({ facette, option }) => (
                        <button
                            key={`${facette.cle}-${option.valeur}`}
                            type="button"
                            onClick={() => basculer(facette.cle, option.valeur)}
                            className="inline-flex items-center gap-1.5 rounded border border-line bg-surface px-2.5 py-1 text-xs hover:border-line-strong"
                        >
                            <span className="text-ink-muted">{facette.libelle} :</span> {option.libelle}
                            <span aria-hidden="true" className="text-ink-faint">×</span>
                            <span className="sr-only">(retirer ce filtre)</span>
                        </button>
                    ))}
                    <button type="button" onClick={toutEffacer} className="px-1 text-xs text-accent hover:underline">
                        Tout effacer
                    </button>
                </div>
            )}

            <div className="mt-6 grid gap-8 lg:grid-cols-[232px_1fr]">
                <aside aria-label="Filtres">
                    <button
                        type="button"
                        onClick={() => setFiltresOuverts(!filtresOuverts)}
                        aria-expanded={filtresOuverts}
                        aria-controls="panneau-filtres"
                        className="flex h-9 w-full items-center justify-between rounded border border-line-strong px-3 text-sm font-medium lg:hidden"
                    >
                        Filtres{actifs > 0 ? ` (${actifs})` : ''}
                        <span aria-hidden="true">{filtresOuverts ? '−' : '+'}</span>
                    </button>
                    <div id="panneau-filtres" className={`${filtresOuverts ? 'block' : 'hidden'} mt-4 lg:mt-0 lg:block`}>
                        {resultats?.facettes.map((facette) => (
                            <FacetGroup
                                key={facette.cle}
                                facette={facette}
                                onBasculer={(valeur) => basculer(facette.cle, valeur)}
                            />
                        ))}
                    </div>
                </aside>

                <section aria-label="Résultats" aria-busy={chargement} className={`min-w-0 ${chargement ? "opacity-60" : ""}`}>
                    {resultats === null && (
                        <p className="rounded border border-line bg-surface p-6 text-sm text-ink-muted">
                            La recherche est momentanément indisponible. Réessayez dans quelques instants.
                        </p>
                    )}

                    {resultats && resultats.pieces.length === 0 && (
                        <div className="rounded border border-line bg-surface p-6 text-sm">
                            <p className="font-medium">Aucune pièce ne correspond à ces critères.</p>
                            <p className="mt-1 text-ink-muted">Retirez un filtre ou modifiez la recherche.</p>
                            {actifs + (filtres.q ? 1 : 0) > 0 && (
                                <button type="button" onClick={toutEffacer} className="mt-3 text-accent hover:underline">
                                    Effacer la recherche et les filtres
                                </button>
                            )}
                        </div>
                    )}

                    {resultats && resultats.pieces.length > 0 && (
                        <>
                            <ul className="grid grid-cols-2 gap-3 sm:gap-4 md:grid-cols-3 xl:grid-cols-4">
                                {resultats.pieces.map((piece) => (
                                    <li key={piece.id}>
                                        <PieceCard piece={piece} />
                                    </li>
                                ))}
                            </ul>

                            <div className="mt-8 space-y-3">
                                <Pagination
                                    page={filtres.page}
                                    pages={resultats.pages}
                                    url={(page) => urlCatalogue({ ...filtres, page })}
                                />
                                {resultats.tronque && filtres.page >= resultats.pages && (
                                    <p className="text-center text-sm text-ink-muted">
                                        Seules les {formaterNombre(resultats.pages * 24)} premières pièces sont
                                        consultables. Affinez la recherche pour voir les suivantes.
                                    </p>
                                )}
                            </div>
                        </>
                    )}
                </section>
            </div>

            {document && (
                <p className="mt-8 text-sm text-ink-muted">
                    <Link href={`/documentation/${document.slug}`} className="text-accent hover:underline">
                        Revenir au document « {document.titre} »
                    </Link>
                </p>
            )}
        </PublicLayout>
    );
}
