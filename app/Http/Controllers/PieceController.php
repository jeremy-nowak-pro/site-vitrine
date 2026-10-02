<?php

namespace App\Http\Controllers;

use App\Enums\TypeConsultation;
use App\Http\Resources\CartePieceResource;
use App\Http\Resources\DocumentLieResource;
use App\Http\Resources\FichePieceResource;
use App\Models\Consultation;
use App\Models\Piece;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

use function Illuminate\Support\defer;

class PieceController extends Controller
{
    private const COMPATIBLES_MAX = 12;

    public function show(Piece $piece): Response
    {
        $piece->load([
            ...FichePieceResource::RELATIONS,
            'compatibles' => fn ($query) => $query
                ->select(CartePieceResource::COLONNES)
                ->with(CartePieceResource::RELATIONS)
                ->orderBy('pieces.nom')
                ->limit(self::COMPATIBLES_MAX),
            'documents' => fn ($query) => $query
                ->select(DocumentLieResource::COLONNES)
                ->orderBy('documents.type')
                ->orderBy('documents.titre'),
        ]);

        // Enregistrée après l'envoi de la réponse : la consultation ne ralentit pas la page.
        defer(fn () => Consultation::create([
            'type' => TypeConsultation::Piece,
            'consultable_id' => $piece->id,
        ]));

        return Inertia::render('Pieces/Show', [
            'piece' => FichePieceResource::make($piece)->resolve(),
            'compatibles' => CartePieceResource::collection($piece->compatibles)->resolve(),
            'documents' => DocumentLieResource::collection($piece->documents)->resolve(),
        ]);
    }

    /**
     * Sert le GLB ou le STL d'une pièce depuis le stockage privé. Le nom de
     * fichier contient la version : le navigateur peut le garder une journée.
     */
    public function fichier(Piece $piece, string $format): StreamedResponse
    {
        $chemin = $format === 'glb' ? $piece->chemin_modele_3d : $piece->chemin_stl;
        abort_if($chemin === null || ! Storage::disk('s3')->exists($chemin), 404);

        $entetes = [
            'Content-Type' => $format === 'glb' ? 'model/gltf-binary' : 'model/stl',
            'Cache-Control' => 'public, max-age=86400',
        ];

        return $format === 'stl'
            ? Storage::disk('s3')->download($chemin, $piece->reference.'.stl', $entetes)
            : Storage::disk('s3')->response($chemin, $piece->reference.'.glb', $entetes);
    }
}
