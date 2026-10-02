<?php

namespace App\Http\Resources;

use App\Models\Piece;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/**
 * Champs d'une carte de pièce, identiques à ceux que le catalogue lit dans
 * l'index Meilisearch.
 *
 * @mixin Piece
 */
class CartePieceResource extends JsonResource
{
    /**
     * Relations à charger avant de construire la carte.
     */
    public const RELATIONS = ['categorie:id,nom', 'echelle:id,libelle', 'fabricant:id,nom', 'modele:id,nom'];

    public const COLONNES = [
        'pieces.id', 'pieces.reference', 'pieces.nom', 'pieces.categorie_id', 'pieces.echelle_id',
        'pieces.fabricant_id', 'pieces.modele_id', 'pieces.chemin_miniature',
    ];

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'reference' => $this->reference,
            'nom' => $this->nom,
            'categorie_nom' => $this->categorie->nom,
            'echelle_libelle' => $this->echelle->libelle,
            'fabricant_nom' => $this->fabricant->nom,
            'modele_nom' => $this->modele->nom,
            'miniature' => $this->chemin_miniature ? Storage::disk('s3')->url($this->chemin_miniature) : null,
        ];
    }
}
