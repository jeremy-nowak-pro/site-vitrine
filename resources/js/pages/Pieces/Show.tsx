import { Head, Link } from '@inertiajs/react';
import { useCallback, useState } from 'react';
import PieceCard from '@/components/catalogue/PieceCard';
import BoutonFavori from '@/components/piece/BoutonFavori';
import ZoneVisionneuse from '@/components/piece/ZoneVisionneuse';
import type { ExportStl } from '@/components/visionneuse/Visionneuse';
import PublicLayout from '@/layouts/PublicLayout';
import { formaterDate, formaterMm, urlFiltre } from '@/lib/catalogue';
import type { CartePiece, DocumentLie, FichePiece } from '@/types/piece';

type Props = {
    piece: FichePiece;
    compatibles: CartePiece[];
    documents: DocumentLie[];
};

function LienFiltre({ href, children }: { href: string; children: React.ReactNode }) {
    return (
        <Link href={href} className="text-accent hover:text-accent-strong hover:underline">
            {children}
        </Link>
    );
}

function telecharger(fichier: Blob, nom: string) {
    const url = URL.createObjectURL(fichier);
    const lien = document.createElement('a');
    lien.href = url;
    lien.download = nom;
    lien.click();
    URL.revokeObjectURL(url);
}

export default function PieceShow({ piece, compatibles, documents }: Props) {
    const [exporterStl, setExporterStl] = useState<ExportStl | null>(null);
    const recevoirExport = useCallback((exporter: ExportStl | null) => setExporterStl(() => exporter), []);
    const caracteristiques: [string, React.ReactNode][] = [
        ['Référence', <span className="font-mono text-[13px]">{piece.reference}</span>],
        ['Catégorie', <LienFiltre href={urlFiltre('categorie', piece.categorie.slug)}>{piece.categorie.nom}</LienFiltre>],
        ['Échelle', <LienFiltre href={urlFiltre('echelle', piece.echelle.slug)}>{piece.echelle.libelle}</LienFiltre>],
        ['Modèle concerné', <LienFiltre href={urlFiltre('modele', piece.modele.slug)}>{piece.modele.nom}</LienFiltre>],
        [
            'Fabricant',
            <>
                <LienFiltre href={urlFiltre('fabricant', piece.fabricant.slug)}>{piece.fabricant.nom}</LienFiltre>
                {piece.fabricant.pays && <span className="text-ink-faint"> ({piece.fabricant.pays})</span>}
            </>,
        ],
        ['Matériau', <LienFiltre href={urlFiltre('materiau', piece.materiau.slug)}>{piece.materiau.nom}</LienFiltre>],
        ['Période', <LienFiltre href={urlFiltre('periode', piece.periode.slug)}>{piece.periode.libelle}</LienFiltre>],
        ['Longueur', formaterMm(piece.dimensions.longueur)],
        ['Largeur', formaterMm(piece.dimensions.largeur)],
        ['Hauteur', formaterMm(piece.dimensions.hauteur)],
    ];

    return (
        <PublicLayout>
            <Head title={`${piece.nom} · ${piece.reference}`} />

            <nav aria-label="Fil d’Ariane" className="text-sm text-ink-muted">
                <ol className="flex flex-wrap items-center gap-1.5">
                    <li>
                        <Link href="/" className="hover:text-ink">
                            Catalogue
                        </Link>
                    </li>
                    <li aria-hidden="true">/</li>
                    <li>
                        <Link href={urlFiltre('categorie', piece.categorie.slug)} className="hover:text-ink">
                            {piece.categorie.nom}
                        </Link>
                    </li>
                    <li aria-hidden="true">/</li>
                    <li aria-current="page" className="font-mono text-xs text-ink-faint">
                        {piece.reference}
                    </li>
                </ol>
            </nav>

            <div className="mt-4 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <h1 className="text-2xl font-semibold tracking-tight">{piece.nom}</h1>
                    <p className="mt-1 text-sm text-ink-muted">
                        {piece.echelle.libelle} · {piece.materiau.nom} · {piece.fabricant.nom}
                    </p>
                </div>
                <div className="flex flex-wrap gap-2">
                    <BoutonFavori reference={piece.reference} />
                    {piece.stl ? (
                        <a
                            href={piece.stl}
                            download
                            className="inline-flex h-9 items-center rounded bg-accent px-3.5 text-sm font-medium text-white hover:bg-accent-strong"
                        >
                            Télécharger le STL
                        </a>
                    ) : (
                        <button
                            type="button"
                            disabled={exporterStl === null}
                            onClick={() => exporterStl && telecharger(exporterStl(), `${piece.reference}.stl`)}
                            title="Généré à partir du modèle affiché"
                            className="inline-flex h-9 items-center rounded bg-accent px-3.5 text-sm font-medium text-white hover:bg-accent-strong disabled:bg-surface-strong disabled:text-ink-faint"
                        >
                            Télécharger le STL
                        </button>
                    )}
                </div>
            </div>

            <div className="mt-6 grid gap-8 lg:grid-cols-[minmax(0,1fr)_340px]">
                <ZoneVisionneuse piece={piece} onExportPret={recevoirExport} />

                <section aria-labelledby="titre-caracteristiques">
                    <h2 id="titre-caracteristiques" className="text-sm font-semibold">
                        Caractéristiques
                    </h2>
                    <dl className="mt-3 divide-y divide-line border-y border-line text-sm">
                        {caracteristiques.map(([terme, valeur]) => (
                            <div key={terme} className="grid grid-cols-[130px_1fr] gap-3 py-2">
                                <dt className="text-ink-muted">{terme}</dt>
                                <dd>{valeur}</dd>
                            </div>
                        ))}
                    </dl>
                </section>
            </div>

            <section aria-labelledby="titre-description" className="mt-10 max-w-3xl">
                <h2 id="titre-description" className="text-sm font-semibold">
                    Description
                </h2>
                <p className="mt-2 leading-relaxed text-ink">{piece.description}</p>
            </section>

            <section aria-labelledby="titre-documents" className="mt-10">
                <h2 id="titre-documents" className="text-sm font-semibold">
                    Documents liés <span className="font-normal text-ink-faint">({documents.length})</span>
                </h2>
                {documents.length === 0 ? (
                    <p className="mt-2 text-sm text-ink-muted">Aucun document pour cette pièce.</p>
                ) : (
                    <ul className="mt-3 divide-y divide-line border-y border-line">
                        {documents.map((document) => (
                            <li key={document.id} className="flex flex-col gap-1 py-2.5 sm:flex-row sm:items-baseline sm:gap-4">
                                <span className="w-40 shrink-0 text-xs text-ink-muted">{document.type_libelle}</span>
                                <Link href={`/documentation/${document.slug}`} className="flex-1 text-sm text-accent hover:underline">
                                    {document.titre}
                                </Link>
                                <span className="text-xs text-ink-faint">
                                    v{document.version} · {formaterDate(document.date_mise_a_jour)}
                                </span>
                            </li>
                        ))}
                    </ul>
                )}
            </section>

            <section aria-labelledby="titre-compatibles" className="mt-10">
                <h2 id="titre-compatibles" className="text-sm font-semibold">
                    Pièces compatibles <span className="font-normal text-ink-faint">({compatibles.length})</span>
                </h2>
                {compatibles.length === 0 ? (
                    <p className="mt-2 text-sm text-ink-muted">Aucune pièce compatible référencée.</p>
                ) : (
                    <ul className="mt-3 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-6">
                        {compatibles.map((compatible) => (
                            <li key={compatible.id}>
                                <PieceCard piece={compatible} />
                            </li>
                        ))}
                    </ul>
                )}
            </section>
        </PublicLayout>
    );
}
