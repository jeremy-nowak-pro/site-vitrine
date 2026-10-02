import type { CartePiece } from '@/types/catalogue';

export type FichePiece = {
    id: number;
    reference: string;
    nom: string;
    description: string;
    categorie: { nom: string; slug: string; forme: string };
    echelle: { libelle: string; slug: string; rapport: number };
    fabricant: { nom: string; slug: string; pays: string | null };
    materiau: { nom: string; slug: string };
    periode: { libelle: string; slug: string };
    modele: { nom: string; slug: string };
    dimensions: { longueur: number; largeur: number; hauteur: number };
    modele_3d: string | null;
    stl: string | null;
    miniature: string | null;
};

export type DocumentLie = {
    id: number;
    titre: string;
    slug: string;
    type: string;
    type_libelle: string;
    version: string;
    date_mise_a_jour: string;
};

export type { CartePiece };
