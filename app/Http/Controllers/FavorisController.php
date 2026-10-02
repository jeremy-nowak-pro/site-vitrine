<?php

namespace App\Http\Controllers;

use App\Http\Resources\CartePieceResource;
use App\Models\Piece;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Les favoris vivent dans le localStorage du navigateur, sans compte. La page
 * envoie la liste des références et reçoit les cartes correspondantes.
 */
class FavorisController extends Controller
{
    private const REFERENCES_MAX = 200;

    public function index(): Response
    {
        return Inertia::render('Favoris/Index');
    }

    public function pieces(Request $request): JsonResponse
    {
        $references = array_slice(array_values(array_unique(array_filter(
            explode(',', (string) $request->query('references', '')),
            fn (string $reference) => preg_match('/^[A-Z0-9-]{1,32}$/', $reference) === 1,
        ))), 0, self::REFERENCES_MAX);

        $pieces = $references === [] ? collect() : Piece::query()
            ->select(CartePieceResource::COLONNES)
            ->with(CartePieceResource::RELATIONS)
            ->whereIn('reference', $references)
            ->get()
            ->sortBy(fn (Piece $piece) => array_search($piece->reference, $references, true))
            ->values();

        return response()->json([
            'pieces' => CartePieceResource::collection($pieces)->resolve($request),
        ]);
    }
}
