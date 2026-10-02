<?php

namespace App\Http\Controllers;

use App\Enums\TypeConsultation;
use App\Enums\TypeDocument;
use App\Http\Resources\CartePieceResource;
use App\Models\Consultation;
use App\Models\Document;
use App\Search\RechercheDocumentation;
use App\Support\ContenuHtml;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

use function Illuminate\Support\defer;

class DocumentationController extends Controller
{
    private const PIECES_AFFICHEES = 40;

    public function index(Request $request, RechercheDocumentation $recherche): Response
    {
        $q = mb_substr(trim((string) $request->query('q', '')), 0, 100);
        $type = TypeDocument::tryFrom((string) $request->query('type', ''));
        $page = filter_var($request->query('page'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 500]]) ?: 1;

        try {
            $resultats = $recherche->rechercher($q, $type, $page);
        } catch (Throwable $e) {
            Log::error('Recherche documentation indisponible', ['exception' => $e->getMessage()]);
            $resultats = null;
        }

        return Inertia::render('Documentation/Index', [
            'filtres' => ['q' => $q, 'type' => $type?->value, 'page' => $page],
            'resultats' => $resultats,
        ]);
    }

    public function show(Document $document): Response
    {
        $nombrePieces = $document->pieces()->count();
        $pieces = $document->pieces()
            ->select(CartePieceResource::COLONNES)
            ->with(CartePieceResource::RELATIONS)
            ->orderBy('pieces.reference')
            ->limit(self::PIECES_AFFICHEES)
            ->get();
        ['html' => $html, 'sommaire' => $sommaire] = ContenuHtml::preparer($document->contenu);

        defer(fn () => Consultation::create([
            'type' => TypeConsultation::Document,
            'consultable_id' => $document->id,
        ]));

        return Inertia::render('Documentation/Show', [
            'document' => [
                'id' => $document->id,
                'titre' => $document->titre,
                'type' => $document->type->value,
                'type_libelle' => $document->type->libelle(),
                'version' => $document->version,
                'auteur' => $document->auteur,
                'date_mise_a_jour' => $document->date_mise_a_jour->toDateString(),
                'html' => $html,
                'sommaire' => $sommaire,
            ],
            'pieces' => CartePieceResource::collection($pieces)->resolve(),
            'nombrePieces' => $nombrePieces,
        ]);
    }
}
