<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Search\RechercheCatalogue;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Formulaire d'ajout de pièce, en lecture seule dans la démo : aucune route
 * d'écriture n'existe.
 */
class PieceController extends Controller
{
    public function create(RechercheCatalogue $recherche): Response
    {
        $referentiels = $recherche->referentiels();

        return Inertia::render('Admin/Pieces/Create', [
            'options' => array_map(
                fn (array $valeurs) => array_map(
                    fn (string $valeur, string $libelle) => ['valeur' => $valeur, 'libelle' => $libelle],
                    array_keys($valeurs),
                    $valeurs,
                ),
                $referentiels,
            ),
            'documents' => Document::query()
                ->orderBy('titre')
                ->get(['id', 'titre', 'type'])
                ->map(fn (Document $document) => [
                    'id' => $document->id,
                    'titre' => $document->titre,
                    'type_libelle' => $document->type->libelle(),
                ]),
        ]);
    }
}
