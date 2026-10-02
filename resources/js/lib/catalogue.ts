import type { CleFacette, EtatCatalogue } from '@/types/catalogue';

const ORDRE_FACETTES: CleFacette[] = ['echelle', 'categorie', 'modele', 'fabricant', 'materiau', 'periode'];

const nombres = new Intl.NumberFormat('fr-FR');

export function formaterNombre(valeur: number): string {
    return nombres.format(valeur);
}

/**
 * Construit l'URL du catalogue. Les valeurs multiples d'une facette sont
 * séparées par des virgules laissées en clair pour garder une URL lisible :
 * /?echelle=1-18,1-43&tri=nom
 */
export function urlCatalogue(etat: EtatCatalogue): string {
    const parametres: string[] = [];
    const ajouter = (cle: string, valeur: string) =>
        parametres.push(`${cle}=${encodeURIComponent(valeur).replace(/%2C/g, ',')}`);

    if (etat.q) ajouter('q', etat.q);
    for (const facette of ORDRE_FACETTES) {
        const valeurs = etat.selection[facette];
        if (valeurs && valeurs.length > 0) ajouter(facette, valeurs.join(','));
    }
    if (etat.document) ajouter('document', String(etat.document));
    if (etat.tri && etat.tri !== triParDefaut(etat.q)) ajouter('tri', etat.tri);
    if (etat.page > 1) ajouter('page', String(etat.page));

    return parametres.length > 0 ? `/?${parametres.join('&')}` : '/';
}

/**
 * Même règle que le serveur : « Nouveautés » sans recherche, « Pertinence » avec.
 */
export function triParDefaut(q: string): string {
    return q === '' ? 'recent' : 'pertinence';
}

export function basculerValeur(etat: EtatCatalogue, facette: CleFacette, valeur: string): EtatCatalogue {
    const actuelles = etat.selection[facette] ?? [];
    const suivantes = actuelles.includes(valeur) ? actuelles.filter((v) => v !== valeur) : [...actuelles, valeur];

    return { ...etat, selection: { ...etat.selection, [facette]: suivantes }, page: 1 };
}

export function nombreFiltresActifs(etat: EtatCatalogue): number {
    return Object.values(etat.selection).reduce((total, valeurs) => total + (valeurs?.length ?? 0), 0) + (etat.document ? 1 : 0);
}
