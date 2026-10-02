import { Head } from '@inertiajs/react';
import { useId, useState, type ReactNode } from 'react';
import AdminLayout from '@/layouts/AdminLayout';

type Option = { valeur: string; libelle: string };

type Props = {
    options: Record<'categorie' | 'echelle' | 'modele' | 'fabricant' | 'materiau' | 'periode', Option[]>;
    documents: { id: number; titre: string; type_libelle: string }[];
};

const champ = 'mt-1.5 h-9 w-full rounded border border-line-strong bg-canvas px-3 text-sm';

function Champ({ libelle, aide, children, id }: { libelle: string; aide?: string; children: ReactNode; id: string }) {
    return (
        <div>
            <label htmlFor={id} className="block text-sm font-medium">
                {libelle}
            </label>
            {children}
            {aide && <p className="mt-1 text-xs text-ink-muted">{aide}</p>}
        </div>
    );
}

function Section({ titre, description, children }: { titre: string; description?: string; children: ReactNode }) {
    return (
        <section className="grid gap-4 border-t border-line py-6 lg:grid-cols-[220px_1fr] lg:gap-10">
            <div>
                <h2 className="text-sm font-semibold">{titre}</h2>
                {description && <p className="mt-1 text-xs text-ink-muted">{description}</p>}
            </div>
            <div className="min-w-0 space-y-4">{children}</div>
        </section>
    );
}

const listes: { cle: keyof Props['options']; libelle: string }[] = [
    { cle: 'categorie', libelle: 'Catégorie' },
    { cle: 'echelle', libelle: 'Échelle' },
    { cle: 'modele', libelle: 'Modèle concerné' },
    { cle: 'fabricant', libelle: 'Fabricant' },
    { cle: 'materiau', libelle: 'Matériau' },
    { cle: 'periode', libelle: 'Période' },
];

export default function CreatePiece({ options, documents }: Props) {
    const prefixe = useId();
    const [filtreDocuments, setFiltreDocuments] = useState('');
    const [documentsChoisis, setDocumentsChoisis] = useState<number[]>([]);
    const documentsVisibles = documents.filter((d) => d.titre.toLowerCase().includes(filtreDocuments.trim().toLowerCase()));
    const id = (nom: string) => `${prefixe}-${nom}`;

    return (
        <AdminLayout>
            <Head title="Ajouter une pièce" />

            <h1 className="text-2xl font-semibold tracking-tight">Ajouter une pièce</h1>
            <p className="mt-1 max-w-2xl text-sm text-ink-muted">
                Tous les champs de la fiche pièce. L’enregistrement est désactivé dans cette démonstration : rien n’est envoyé au
                serveur.
            </p>

            <form className="mt-6" onSubmit={(e) => e.preventDefault()} aria-describedby={id('desactive')}>
                <Section titre="Identification">
                    <div className="grid gap-4 sm:grid-cols-[220px_1fr]">
                        <Champ id={id('reference')} libelle="Référence" aide="Unique, 32 caractères max.">
                            <input id={id('reference')} name="reference" maxLength={32} placeholder="ALU-JA18-000123" className={`${champ} font-mono`} />
                        </Champ>
                        <Champ id={id('nom')} libelle="Nom">
                            <input id={id('nom')} name="nom" maxLength={160} className={champ} />
                        </Champ>
                    </div>
                    <Champ id={id('description')} libelle="Description">
                        <textarea id={id('description')} name="description" rows={4} className={`${champ} h-auto py-2`} />
                    </Champ>
                </Section>

                <Section titre="Classement" description="Valeurs des tables de référence, utilisées par les filtres du catalogue.">
                    <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                        {listes.map(({ cle, libelle }) => (
                            <Champ key={cle} id={id(cle)} libelle={libelle}>
                                <select id={id(cle)} name={cle} defaultValue="" className={`${champ} px-2`}>
                                    <option value="" disabled>
                                        Choisir…
                                    </option>
                                    {options[cle].map((option) => (
                                        <option key={option.valeur} value={option.valeur}>
                                            {option.libelle}
                                        </option>
                                    ))}
                                </select>
                            </Champ>
                        ))}
                    </div>
                </Section>

                <Section titre="Dimensions réelles" description="En millimètres. Calculées depuis le GLB si laissées vides.">
                    <div className="grid gap-4 sm:grid-cols-3">
                        {(['longueur', 'largeur', 'hauteur'] as const).map((dimension) => (
                            <Champ key={dimension} id={id(dimension)} libelle={dimension[0].toUpperCase() + dimension.slice(1)}>
                                <div className="relative">
                                    <input
                                        id={id(dimension)}
                                        name={`${dimension}_mm`}
                                        type="number"
                                        min={0}
                                        step={0.01}
                                        inputMode="decimal"
                                        className={`${champ} pr-10 tabular-nums`}
                                    />
                                    <span className="pointer-events-none absolute top-1/2 right-3 mt-[3px] -translate-y-1/2 text-xs text-ink-faint">mm</span>
                                </div>
                            </Champ>
                        ))}
                    </div>
                </Section>

                <Section titre="Fichiers" description="Stockés sur le disque S3, servis par l’application.">
                    <div className="grid gap-4 sm:grid-cols-3">
                        <Champ id={id('glb')} libelle="Modèle 3D (GLB)" aide="Compressé à l’import.">
                            <input id={id('glb')} name="modele_3d" type="file" accept=".glb,model/gltf-binary" className="mt-1.5 block w-full text-sm file:mr-3 file:rounded file:border file:border-line-strong file:bg-canvas file:px-3 file:py-1.5 file:text-sm" />
                        </Champ>
                        <Champ id={id('stl')} libelle="Fichier STL" aide="Généré depuis le GLB si absent.">
                            <input id={id('stl')} name="stl" type="file" accept=".stl,model/stl" className="mt-1.5 block w-full text-sm file:mr-3 file:rounded file:border file:border-line-strong file:bg-canvas file:px-3 file:py-1.5 file:text-sm" />
                        </Champ>
                        <Champ id={id('miniature')} libelle="Miniature" aide="Générée depuis le GLB si absente.">
                            <input id={id('miniature')} name="miniature" type="file" accept="image/png,image/jpeg,image/webp,image/svg+xml" className="mt-1.5 block w-full text-sm file:mr-3 file:rounded file:border file:border-line-strong file:bg-canvas file:px-3 file:py-1.5 file:text-sm" />
                        </Champ>
                    </div>
                </Section>

                <Section titre="Documents liés" description={`${documentsChoisis.length} sélectionné${documentsChoisis.length > 1 ? 's' : ''} sur ${documents.length}.`}>
                    <Champ id={id('filtre-documents')} libelle="Filtrer les documents">
                        <input
                            id={id('filtre-documents')}
                            type="search"
                            value={filtreDocuments}
                            onChange={(e) => setFiltreDocuments(e.target.value)}
                            placeholder="Titre…"
                            className={champ}
                        />
                    </Champ>
                    <fieldset>
                        <legend className="sr-only">Documents liés</legend>
                        <ul className="max-h-72 divide-y divide-line overflow-y-auto rounded border border-line">
                            {documentsVisibles.map((document) => (
                                <li key={document.id}>
                                    <label className="flex cursor-pointer items-start gap-2.5 px-3 py-2 text-sm hover:bg-surface">
                                        <input
                                            type="checkbox"
                                            name="documents[]"
                                            value={document.id}
                                            checked={documentsChoisis.includes(document.id)}
                                            onChange={() =>
                                                setDocumentsChoisis((choisis) =>
                                                    choisis.includes(document.id) ? choisis.filter((d) => d !== document.id) : [...choisis, document.id],
                                                )
                                            }
                                            className="mt-0.5 size-4 shrink-0 accent-accent"
                                        />
                                        <span>
                                            {document.titre}
                                            <span className="block text-xs text-ink-muted">{document.type_libelle}</span>
                                        </span>
                                    </label>
                                </li>
                            ))}
                            {documentsVisibles.length === 0 && <li className="px-3 py-2 text-sm text-ink-muted">Aucun document.</li>}
                        </ul>
                    </fieldset>
                </Section>

                <Section titre="Pièces compatibles" description="Relation enregistrée dans les deux sens.">
                    <Champ id={id('compatibles')} libelle="Références" aide="Une par ligne.">
                        <textarea id={id('compatibles')} name="compatibles" rows={3} className={`${champ} h-auto py-2 font-mono`} />
                    </Champ>
                </Section>

                <div className="flex flex-col gap-3 border-t border-line pt-6 sm:flex-row sm:items-center sm:justify-end">
                    <p id={id('desactive')} className="text-sm text-ink-muted">
                        Enregistrement désactivé dans la version de démonstration.
                    </p>
                    <button type="submit" disabled className="h-9 rounded bg-surface-strong px-4 text-sm font-medium text-ink-faint">
                        Enregistrer la pièce
                    </button>
                </div>
            </form>
        </AdminLayout>
    );
}
