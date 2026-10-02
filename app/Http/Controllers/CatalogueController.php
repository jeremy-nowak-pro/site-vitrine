<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Search\FiltresCatalogue;
use App\Search\RechercheCatalogue;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class CatalogueController extends Controller
{
    public function __invoke(Request $request, RechercheCatalogue $recherche): Response
    {
        $filtres = FiltresCatalogue::depuisRequete($request);

        try {
            $resultats = $recherche->rechercher($filtres);
        } catch (Throwable $e) {
            Log::error('Recherche catalogue indisponible', ['exception' => $e->getMessage()]);
            $resultats = null;
        }

        return Inertia::render('Catalogue/Index', [
            'filtres' => $filtres->versTableau(),
            'resultats' => $resultats,
            'document' => $filtres->document
                ? Document::query()->select('id', 'titre', 'slug')->find($filtres->document)
                : null,
            'facettes' => FiltresCatalogue::FACETTES,
            'tris' => [
                ['valeur' => 'pertinence', 'libelle' => 'Pertinence'],
                ['valeur' => 'recent', 'libelle' => 'Nouveautés'],
                ['valeur' => 'nom', 'libelle' => 'Nom (A à Z)'],
                ['valeur' => 'reference', 'libelle' => 'Référence'],
                ['valeur' => 'echelle', 'libelle' => 'Échelle (grande à petite)'],
                ['valeur' => 'longueur', 'libelle' => 'Longueur (décroissante)'],
            ],
        ]);
    }
}
