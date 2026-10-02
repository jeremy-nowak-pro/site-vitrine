import { Head } from '@inertiajs/react';
import { useRef, useState } from 'react';
import AdminLayout from '@/layouts/AdminLayout';

type Colonne = { nom: string; obligatoire: boolean; description: string; exemple: string };

type Analyse = {
    nom: string;
    taille: number;
    lignes: number;
    entete: string[];
    apercu: string[][];
    manquantes: string[];
    inconnues: string[];
};

const TRAITEMENTS = [
    ['Validation', 'Colonnes obligatoires, unicité des références, existence des slugs (catégorie, échelle, modèle…) et des documents cités. Les lignes en erreur sont listées sans bloquer les autres.'],
    ['Compression des modèles 3D', 'Les GLB sont optimisés (fusion des maillages identiques, compression Draco ou Meshopt) puis déposés sur le stockage S3.'],
    ['Calcul des dimensions', 'Si longueur, largeur ou hauteur manquent, elles sont lues dans la boîte englobante du GLB, en millimètres.'],
    ['Génération des fichiers dérivés', 'Miniature rendue depuis le modèle 3D et STL exporté quand ils ne sont pas fournis.'],
    ['Indexation', 'Les pièces importées sont envoyées à Meilisearch par lots de 2 000, en file d’attente, sans interrompre la recherche.'],
] as const;

/**
 * Découpe une ligne CSV en respectant les champs entre guillemets.
 */
function decouper(ligne: string, separateur: string): string[] {
    const champs: string[] = [];
    let courant = '';
    let entreGuillemets = false;
    for (let i = 0; i < ligne.length; i++) {
        const caractere = ligne[i];
        if (caractere === '"') {
            if (entreGuillemets && ligne[i + 1] === '"') {
                courant += '"';
                i++;
            } else {
                entreGuillemets = !entreGuillemets;
            }
        } else if (caractere === separateur && !entreGuillemets) {
            champs.push(courant);
            courant = '';
        } else {
            courant += caractere;
        }
    }
    champs.push(courant);

    return champs.map((champ) => champ.trim());
}

const octets = (taille: number) =>
    taille < 1024 ? `${taille} o` : taille < 1048576 ? `${(taille / 1024).toFixed(1)} Ko` : `${(taille / 1048576).toFixed(1)} Mo`;

export default function Import({ colonnes }: { colonnes: Colonne[] }) {
    const [analyse, setAnalyse] = useState<Analyse | null>(null);
    const [erreur, setErreur] = useState<string | null>(null);
    const [survol, setSurvol] = useState(false);
    const champFichier = useRef<HTMLInputElement>(null);

    const analyser = async (fichier: File) => {
        setErreur(null);
        if (!fichier.name.toLowerCase().endsWith('.csv')) {
            setAnalyse(null);
            setErreur('Le fichier doit être au format CSV.');
            return;
        }
        // Seul le début du fichier est lu : l'aperçu ne charge pas un CSV de plusieurs centaines de Mo.
        const debut = await fichier.slice(0, 256 * 1024).text();
        const lignes = debut.replace(/^﻿/, '').split(/\r?\n/).filter((l) => l.trim() !== '');
        if (lignes.length === 0) {
            setAnalyse(null);
            setErreur('Le fichier est vide.');
            return;
        }
        const separateur = (lignes[0].match(/;/g)?.length ?? 0) > (lignes[0].match(/,/g)?.length ?? 0) ? ';' : ',';
        const entete = decouper(lignes[0], separateur).map((c) => c.toLowerCase());
        const attendues = colonnes.map((c) => c.nom);

        setAnalyse({
            nom: fichier.name,
            taille: fichier.size,
            lignes: lignes.length - 1,
            entete,
            apercu: lignes.slice(1, 6).map((l) => decouper(l, separateur)),
            manquantes: colonnes.filter((c) => c.obligatoire && !entete.includes(c.nom)).map((c) => c.nom),
            inconnues: entete.filter((c) => !attendues.includes(c)),
        });
    };

    const telechargerModele = () => {
        const echapper = (v: string) => (/[;"\n]/.test(v) ? `"${v.replace(/"/g, '""')}"` : v);
        const contenu = [colonnes.map((c) => c.nom).join(';'), colonnes.map((c) => echapper(c.exemple)).join(';')].join('\n');
        const url = URL.createObjectURL(new Blob(['﻿' + contenu], { type: 'text/csv;charset=utf-8' }));
        const lien = document.createElement('a');
        lien.href = url;
        lien.download = 'modele-import-pieces.csv';
        lien.click();
        URL.revokeObjectURL(url);
    };

    return (
        <AdminLayout>
            <Head title="Import CSV" />

            <div className="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <h1 className="text-2xl font-semibold tracking-tight">Import CSV</h1>
                    <p className="mt-1 max-w-2xl text-sm text-ink-muted">
                        Ajout de pièces en masse. Le fichier déposé est seulement analysé dans votre navigateur : rien n’est envoyé.
                    </p>
                </div>
                <button type="button" onClick={telechargerModele} className="h-9 self-start rounded border border-line-strong px-3 text-sm font-medium hover:bg-surface sm:self-auto">
                    Télécharger le modèle CSV
                </button>
            </div>

            <div
                onDragOver={(e) => {
                    e.preventDefault();
                    setSurvol(true);
                }}
                onDragLeave={() => setSurvol(false)}
                onDrop={(e) => {
                    e.preventDefault();
                    setSurvol(false);
                    const fichier = e.dataTransfer.files[0];
                    if (fichier) void analyser(fichier);
                }}
                className={`mt-6 flex flex-col items-center justify-center gap-2 rounded-md border border-dashed px-6 py-10 text-center ${
                    survol ? 'border-accent bg-accent-soft' : 'border-line-strong bg-surface'
                }`}
            >
                <p className="text-sm font-medium">Déposez un fichier CSV ici</p>
                <p className="text-xs text-ink-muted">Séparateur « ; » ou « , », encodage UTF-8, une pièce par ligne.</p>
                <button
                    type="button"
                    onClick={() => champFichier.current?.click()}
                    className="mt-2 h-8 rounded border border-line-strong bg-canvas px-3 text-sm hover:bg-surface"
                >
                    Choisir un fichier
                </button>
                <input
                    ref={champFichier}
                    type="file"
                    accept=".csv,text/csv"
                    className="sr-only"
                    tabIndex={-1}
                    aria-label="Fichier CSV"
                    onChange={(e) => {
                        const fichier = e.target.files?.[0];
                        if (fichier) void analyser(fichier);
                    }}
                />
            </div>

            <div aria-live="polite">
                {erreur && <p className="mt-3 text-sm text-red-700">{erreur}</p>}

                {analyse && (
                    <section className="mt-4 rounded-md border border-line p-4" aria-label="Analyse du fichier">
                        <p className="text-sm">
                            <span className="font-medium">{analyse.nom}</span>{' '}
                            <span className="text-ink-muted">
                                · {octets(analyse.taille)} · {analyse.lignes} ligne{analyse.lignes > 1 ? 's' : ''} lue{analyse.lignes > 1 ? 's' : ''}
                                {analyse.taille > 256 * 1024 && ' (aperçu des 256 premiers Ko)'}
                            </span>
                        </p>
                        <ul className="mt-2 space-y-1 text-sm">
                            <li className={analyse.manquantes.length ? 'text-red-700' : 'text-ink'}>
                                {analyse.manquantes.length
                                    ? `Colonnes obligatoires absentes : ${analyse.manquantes.join(', ')}`
                                    : '✓ Toutes les colonnes obligatoires sont présentes'}
                            </li>
                            {analyse.inconnues.length > 0 && (
                                <li className="text-ink-muted">Colonnes ignorées : {analyse.inconnues.join(', ')}</li>
                            )}
                        </ul>
                        {analyse.apercu.length > 0 && (
                            <div className="mt-3 overflow-x-auto">
                                <table className="w-full text-xs">
                                    <thead>
                                        <tr className="text-left text-ink-muted">
                                            {analyse.entete.map((colonne, i) => (
                                                <th key={i} scope="col" className="border-b border-line px-2 py-1.5 font-medium whitespace-nowrap">
                                                    {colonne}
                                                </th>
                                            ))}
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {analyse.apercu.map((ligne, i) => (
                                            <tr key={i}>
                                                {ligne.map((valeur, j) => (
                                                    <td key={j} className="max-w-48 truncate border-b border-line px-2 py-1.5">
                                                        {valeur}
                                                    </td>
                                                ))}
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        )}
                    </section>
                )}
            </div>

            <div className="mt-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-end">
                <p className="text-sm text-ink-muted">Import désactivé dans la version de démonstration.</p>
                <button type="button" disabled className="h-9 rounded bg-surface-strong px-4 text-sm font-medium text-ink-faint">
                    Lancer l’import
                </button>
            </div>

            <section aria-labelledby="titre-colonnes" className="mt-10">
                <h2 id="titre-colonnes" className="text-sm font-semibold">
                    Colonnes attendues
                </h2>
                <div className="mt-3 overflow-x-auto">
                    <table className="w-full text-sm">
                        <thead>
                            <tr className="text-left text-xs text-ink-muted">
                                <th scope="col" className="border-b border-line py-2 pr-4 font-medium">Colonne</th>
                                <th scope="col" className="border-b border-line py-2 pr-4 font-medium">Obligatoire</th>
                                <th scope="col" className="border-b border-line py-2 pr-4 font-medium">Contenu</th>
                                <th scope="col" className="border-b border-line py-2 font-medium">Exemple</th>
                            </tr>
                        </thead>
                        <tbody>
                            {colonnes.map((colonne) => (
                                <tr key={colonne.nom}>
                                    <td className="border-b border-line py-2 pr-4 font-mono text-[13px] whitespace-nowrap">{colonne.nom}</td>
                                    <td className="border-b border-line py-2 pr-4">{colonne.obligatoire ? 'Oui' : <span className="text-ink-muted">Non</span>}</td>
                                    <td className="border-b border-line py-2 pr-4 text-ink-muted">{colonne.description}</td>
                                    <td className="border-b border-line py-2 font-mono text-[12px] break-all text-ink-muted">{colonne.exemple || '—'}</td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </section>

            <section aria-labelledby="titre-traitements" className="mt-10 max-w-3xl">
                <h2 id="titre-traitements" className="text-sm font-semibold">
                    Traitements automatiques prévus
                </h2>
                <ol className="mt-3 space-y-3">
                    {TRAITEMENTS.map(([titre, description], i) => (
                        <li key={titre} className="grid grid-cols-[24px_1fr] gap-2 text-sm">
                            <span className="text-ink-faint tabular-nums">{i + 1}.</span>
                            <span>
                                <span className="font-medium">{titre}.</span> <span className="text-ink-muted">{description}</span>
                            </span>
                        </li>
                    ))}
                </ol>
            </section>
        </AdminLayout>
    );
}
