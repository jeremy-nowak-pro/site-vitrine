import { Head, Link, router } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';
import Pagination from '@/components/Pagination';
import PublicLayout from '@/layouts/PublicLayout';
import { formaterDate, formaterNombre } from '@/lib/catalogue';
import type { FiltresDocumentation, ResultatsDocumentation } from '@/types/documentation';

type Props = {
    filtres: FiltresDocumentation;
    resultats: ResultatsDocumentation | null;
};

function urlDocumentation({ q, type, page }: FiltresDocumentation): string {
    const parametres = new URLSearchParams();
    if (q) parametres.set('q', q);
    if (type) parametres.set('type', type);
    if (page > 1) parametres.set('page', String(page));
    const chaine = parametres.toString();

    return chaine ? `/documentation?${chaine}` : '/documentation';
}

export default function DocumentationIndex({ filtres, resultats }: Props) {
    const [saisie, setSaisie] = useState(filtres.q);
    const champ = useRef<HTMLInputElement>(null);
    const totalTypes = resultats?.types.reduce((total, type) => total + type.nombre, 0) ?? 0;

    useEffect(() => {
        if (saisie.trim() === filtres.q) return;
        const minuteur = window.setTimeout(() => {
            router.get(urlDocumentation({ ...filtres, q: saisie.trim(), page: 1 }), {}, {
                preserveState: true,
                preserveScroll: true,
                replace: true,
            });
        }, 300);

        return () => window.clearTimeout(minuteur);
    }, [saisie]);

    useEffect(() => {
        if (champ.current !== document.activeElement) setSaisie(filtres.q);
    }, [filtres.q]);

    const ongletsType = [{ valeur: null, libelle: 'Tous', nombre: totalTypes }, ...(resultats?.types ?? [])];

    return (
        <PublicLayout>
            <Head title="Documentation" />

            <div className="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <h1 className="text-2xl font-semibold tracking-tight">Documentation</h1>
                    <p className="mt-1 text-sm text-ink-muted" aria-live="polite">
                        {resultats
                            ? `${formaterNombre(resultats.total)} document${resultats.total > 1 ? 's' : ''}`
                            : 'Recherche indisponible'}
                    </p>
                </div>
                <form role="search" onSubmit={(e) => e.preventDefault()}>
                    <label htmlFor="recherche-doc" className="sr-only">
                        Rechercher dans la documentation
                    </label>
                    <input
                        ref={champ}
                        id="recherche-doc"
                        type="search"
                        value={saisie}
                        onChange={(e) => setSaisie(e.target.value)}
                        placeholder="Titre, contenu, auteur…"
                        autoComplete="off"
                        className="h-9 w-full rounded border border-line-strong bg-canvas px-3 text-sm placeholder:text-ink-faint sm:w-80"
                    />
                </form>
            </div>

            <nav aria-label="Type de document" className="mt-6 border-b border-line">
                <ul className="-mb-px flex gap-1 overflow-x-auto">
                    {ongletsType.map((type) => {
                        const actif = filtres.type === type.valeur;
                        return (
                            <li key={type.valeur ?? 'tous'} className="shrink-0">
                                <Link
                                    href={urlDocumentation({ ...filtres, type: type.valeur, page: 1 })}
                                    preserveScroll
                                    aria-current={actif ? 'page' : undefined}
                                    className={`inline-flex items-center gap-1.5 border-b-2 px-3 py-2 text-sm ${
                                        actif ? 'border-accent font-medium text-ink' : 'border-transparent text-ink-muted hover:text-ink'
                                    }`}
                                >
                                    {type.libelle}
                                    <span className="text-xs text-ink-faint tabular-nums">{type.nombre}</span>
                                </Link>
                            </li>
                        );
                    })}
                </ul>
            </nav>

            {resultats === null && (
                <p className="mt-6 rounded border border-line bg-surface p-6 text-sm text-ink-muted">
                    La recherche est momentanément indisponible. Réessayez dans quelques instants.
                </p>
            )}

            {resultats && resultats.documents.length === 0 && (
                <p className="mt-6 rounded border border-line bg-surface p-6 text-sm text-ink-muted">
                    Aucun document ne correspond à cette recherche.
                </p>
            )}

            {resultats && resultats.documents.length > 0 && (
                <>
                    <ul className="divide-y divide-line">
                        {resultats.documents.map((document) => (
                            <li key={document.id} className="py-4">
                                <p className="text-xs text-ink-muted">{document.type_libelle}</p>
                                <h2 className="mt-0.5 text-[15px] font-medium">
                                    <Link href={`/documentation/${document.slug}`} className="hover:text-accent hover:underline">
                                        {document.titre}
                                    </Link>
                                </h2>
                                <p className="mt-1 line-clamp-2 max-w-3xl text-sm text-ink-muted">{document.extrait}</p>
                                <p className="mt-1.5 text-xs text-ink-faint">
                                    v{document.version} · {document.auteur} · mis à jour le {formaterDate(document.date_mise_a_jour)} ·{' '}
                                    {document.pieces_count} pièce{document.pieces_count > 1 ? 's' : ''} concernée
                                    {document.pieces_count > 1 ? 's' : ''}
                                </p>
                            </li>
                        ))}
                    </ul>
                    <div className="mt-6">
                        <Pagination page={filtres.page} pages={resultats.pages} url={(page) => urlDocumentation({ ...filtres, page })} />
                    </div>
                </>
            )}
        </PublicLayout>
    );
}
