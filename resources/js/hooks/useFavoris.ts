import { useCallback, useSyncExternalStore } from 'react';

const CLE = 'catalogue:favoris';
const EVENEMENT = 'catalogue:favoris';
const VIDE: string[] = [];

let cache: { brut: string | null; references: string[] } = { brut: null, references: VIDE };

/**
 * Lecture protégée : en navigation privée ou avec le stockage bloqué,
 * localStorage peut lever une exception. Les favoris sont alors vides.
 */
function lire(): string[] {
    let brut: string | null = null;
    try {
        brut = window.localStorage.getItem(CLE);
    } catch {
        return VIDE;
    }
    if (brut === cache.brut) {
        return cache.references;
    }
    let references: string[] = VIDE;
    try {
        const valeur: unknown = brut ? JSON.parse(brut) : [];
        references = Array.isArray(valeur) ? valeur.filter((r): r is string => typeof r === 'string') : VIDE;
    } catch {
        references = VIDE;
    }
    cache = { brut, references };

    return references;
}

function ecrire(references: string[]): void {
    try {
        window.localStorage.setItem(CLE, JSON.stringify(references));
    } catch {
        // Stockage indisponible : le favori reste actif jusqu'au rechargement seulement.
        cache = { brut: JSON.stringify(references), references };
    }
    window.dispatchEvent(new Event(EVENEMENT));
}

function abonner(rappel: () => void): () => void {
    // « storage » couvre les autres onglets, l'événement maison l'onglet courant.
    window.addEventListener('storage', rappel);
    window.addEventListener(EVENEMENT, rappel);

    return () => {
        window.removeEventListener('storage', rappel);
        window.removeEventListener(EVENEMENT, rappel);
    };
}

export function useFavoris() {
    const references = useSyncExternalStore(abonner, lire, () => VIDE);

    const basculer = useCallback((reference: string) => {
        const actuelles = lire();
        ecrire(
            actuelles.includes(reference)
                ? actuelles.filter((r) => r !== reference)
                : [reference, ...actuelles],
        );
    }, []);

    const retirer = useCallback((reference: string) => ecrire(lire().filter((r) => r !== reference)), []);
    const vider = useCallback(() => ecrire([]), []);

    return {
        references,
        estFavori: (reference: string) => references.includes(reference),
        basculer,
        retirer,
        vider,
    };
}
