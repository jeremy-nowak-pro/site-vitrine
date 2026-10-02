<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class ImportController extends Controller
{
    /**
     * Colonnes attendues : nom, obligatoire, description, exemple.
     */
    public const COLONNES = [
        ['reference', true, 'Référence unique, 32 caractères max.', 'ALU-JA18-000123'],
        ['nom', true, 'Nom affiché de la pièce.', 'Jante 5 branches pour Vortex GT'],
        ['description', true, 'Texte libre.', 'Jante en aluminium tourné, finition brute.'],
        ['categorie', true, 'Slug d’une catégorie existante.', 'jantes-et-pneus'],
        ['echelle', true, 'Slug d’une échelle existante.', '1-18'],
        ['modele', true, 'Slug d’un modèle concerné existant.', 'vortex-gt'],
        ['fabricant', true, 'Slug d’un fabricant existant.', 'atelier-lumiere'],
        ['materiau', true, 'Slug d’un matériau existant.', 'aluminium-tourne'],
        ['periode', true, 'Slug d’une période existante.', '1990-2009'],
        ['longueur_mm', false, 'Calculée depuis le GLB si vide.', '36.4'],
        ['largeur_mm', false, 'Calculée depuis le GLB si vide.', '36.4'],
        ['hauteur_mm', false, 'Calculée depuis le GLB si vide.', '12.8'],
        ['fichier_glb', false, 'Nom du fichier dans l’archive ZIP jointe.', 'ALU-JA18-000123.glb'],
        ['fichier_stl', false, 'Généré depuis le GLB si vide.', 'ALU-JA18-000123.stl'],
        ['miniature', false, 'Générée depuis le GLB si vide.', ''],
        ['documents', false, 'Slugs de documents séparés par « | ».', 'montage-des-jantes-et-pneus-de-la-vortex-gt'],
        ['compatibles', false, 'Références séparées par « | ».', 'ALU-SU18-000124|ALU-VO18-000125'],
    ];

    public function __invoke(): Response
    {
        return Inertia::render('Admin/Import', [
            'colonnes' => array_map(fn (array $colonne) => [
                'nom' => $colonne[0],
                'obligatoire' => $colonne[1],
                'description' => $colonne[2],
                'exemple' => $colonne[3],
            ], self::COLONNES),
        ]);
    }
}
