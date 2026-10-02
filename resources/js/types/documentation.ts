export type LigneDocument = {
    id: number;
    titre: string;
    slug: string;
    type: string;
    type_libelle: string;
    auteur: string;
    version: string;
    date_mise_a_jour: string;
    pieces_count: number;
    extrait: string;
};

export type ResultatsDocumentation = {
    documents: LigneDocument[];
    total: number;
    pages: number;
    types: { valeur: string; libelle: string; nombre: number }[];
};

export type FiltresDocumentation = { q: string; type: string | null; page: number };
