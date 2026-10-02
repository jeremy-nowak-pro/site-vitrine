export type CleFacette = 'echelle' | 'categorie' | 'modele' | 'fabricant' | 'materiau' | 'periode';

export type Selection = Partial<Record<CleFacette, string[]>>;

export type EtatCatalogue = {
    q: string;
    selection: Selection;
    document: number | null;
    tri: string;
    page: number;
};

export type OptionFacette = {
    valeur: string;
    libelle: string;
    nombre: number;
    actif: boolean;
};

export type Facette = {
    cle: CleFacette;
    libelle: string;
    options: OptionFacette[];
};

export type CartePiece = {
    id: number;
    reference: string;
    nom: string;
    categorie_nom: string;
    echelle_libelle: string;
    fabricant_nom: string;
    modele_nom: string;
    miniature: string | null;
};

export type ResultatsCatalogue = {
    pieces: CartePiece[];
    total: number;
    pages: number;
    tronque: boolean;
    facettes: Facette[];
};
