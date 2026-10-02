import { Head, Link } from '@inertiajs/react';
import type { ReactNode } from 'react';
import CourbeJournaliere from '@/components/admin/CourbeJournaliere';
import AdminLayout, { EtiquetteFictive } from '@/layouts/AdminLayout';
import { formaterNombre } from '@/lib/catalogue';

type Props = {
    indicateurs: { libelle: string; valeur: number; evolution: number }[];
    serie: { date: string; pieces: number; documents: number }[];
    piecesPopulaires: { reference: string; nom: string; echelle: string; consultations: number }[];
    documentsPopulaires: { slug: string; titre: string; type: string; consultations: number }[];
};

function Bloc({ titre, children }: { titre: string; children: ReactNode }) {
    return (
        <section className="rounded-md border border-line p-4 sm:p-5">
            <div className="flex items-start justify-between gap-3">
                <h2 className="text-sm font-semibold">{titre}</h2>
                <EtiquetteFictive />
            </div>
            <div className="mt-4">{children}</div>
        </section>
    );
}

export default function Statistiques({ indicateurs, serie, piecesPopulaires, documentsPopulaires }: Props) {
    return (
        <AdminLayout>
            <Head title="Statistiques" />

            <h1 className="text-2xl font-semibold tracking-tight">Statistiques</h1>
            <p className="mt-1 text-sm text-ink-muted">30 derniers jours, comparés aux 30 jours précédents.</p>

            <ul className="mt-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                {indicateurs.map((indicateur) => (
                    <li key={indicateur.libelle} className="rounded-md border border-line p-4">
                        <div className="flex items-start justify-between gap-2">
                            <p className="text-sm text-ink-muted">{indicateur.libelle}</p>
                            <EtiquetteFictive />
                        </div>
                        <p className="mt-2 text-2xl font-semibold tracking-tight tabular-nums">{formaterNombre(indicateur.valeur)}</p>
                        <p className="mt-0.5 text-xs text-ink-muted tabular-nums">
                            {indicateur.evolution >= 0 ? '+' : '−'}
                            {Math.abs(indicateur.evolution)} % sur 30 jours
                        </p>
                    </li>
                ))}
            </ul>

            <div className="mt-4">
                <Bloc titre="Consultations par jour">
                    <div className="grid gap-8 lg:grid-cols-2">
                        <CourbeJournaliere
                            titre="Fiches pièces"
                            unite="consultations"
                            points={serie.map((jour) => ({ date: jour.date, valeur: jour.pieces }))}
                        />
                        <CourbeJournaliere
                            titre="Documents"
                            unite="consultations"
                            points={serie.map((jour) => ({ date: jour.date, valeur: jour.documents }))}
                        />
                    </div>
                </Bloc>
            </div>

            <div className="mt-4 grid gap-4 lg:grid-cols-2">
                <Bloc titre="Pièces les plus consultées">
                    <table className="w-full text-sm">
                        <thead>
                            <tr className="text-left text-xs text-ink-muted">
                                <th scope="col" className="w-8 pb-2 font-medium">#</th>
                                <th scope="col" className="pb-2 font-medium">Pièce</th>
                                <th scope="col" className="pb-2 text-right font-medium">Consultations</th>
                            </tr>
                        </thead>
                        <tbody>
                            {piecesPopulaires.map((piece, rang) => (
                                <tr key={piece.reference} className="border-t border-line">
                                    <td className="py-2 text-ink-faint tabular-nums">{rang + 1}</td>
                                    <td className="py-2">
                                        <Link href={`/pieces/${piece.reference}`} className="hover:text-accent hover:underline">
                                            {piece.nom}
                                        </Link>
                                        <span className="block font-mono text-[11px] text-ink-faint">
                                            {piece.reference} · {piece.echelle}
                                        </span>
                                    </td>
                                    <td className="py-2 text-right tabular-nums">{formaterNombre(piece.consultations)}</td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </Bloc>

                <Bloc titre="Documents les plus consultés">
                    <table className="w-full text-sm">
                        <thead>
                            <tr className="text-left text-xs text-ink-muted">
                                <th scope="col" className="w-8 pb-2 font-medium">#</th>
                                <th scope="col" className="pb-2 font-medium">Document</th>
                                <th scope="col" className="pb-2 text-right font-medium">Consultations</th>
                            </tr>
                        </thead>
                        <tbody>
                            {documentsPopulaires.map((document, rang) => (
                                <tr key={document.slug} className="border-t border-line">
                                    <td className="py-2 text-ink-faint tabular-nums">{rang + 1}</td>
                                    <td className="py-2">
                                        <Link href={`/documentation/${document.slug}`} className="hover:text-accent hover:underline">
                                            {document.titre}
                                        </Link>
                                        <span className="block text-xs text-ink-faint">{document.type}</span>
                                    </td>
                                    <td className="py-2 text-right tabular-nums">{formaterNombre(document.consultations)}</td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </Bloc>
            </div>
        </AdminLayout>
    );
}
