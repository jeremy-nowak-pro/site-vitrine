<?php

namespace App\Http\Resources;

use App\Models\Piece;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/**
 * @mixin Piece
 */
class FichePieceResource extends JsonResource
{
    public const RELATIONS = [
        'categorie:id,nom,slug,forme',
        'echelle:id,libelle,slug,rapport',
        'fabricant:id,nom,slug,pays',
        'materiau:id,nom,slug',
        'periode:id,libelle,slug',
        'modele:id,nom,slug',
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
            'description' => $this->description,
            'categorie' => ['nom' => $this->categorie->nom, 'slug' => $this->categorie->slug, 'forme' => $this->categorie->forme],
            'echelle' => ['libelle' => $this->echelle->libelle, 'slug' => $this->echelle->slug, 'rapport' => $this->echelle->rapport],
            'fabricant' => ['nom' => $this->fabricant->nom, 'slug' => $this->fabricant->slug, 'pays' => $this->fabricant->pays],
            'materiau' => ['nom' => $this->materiau->nom, 'slug' => $this->materiau->slug],
            'periode' => ['libelle' => $this->periode->libelle, 'slug' => $this->periode->slug],
            'modele' => ['nom' => $this->modele->nom, 'slug' => $this->modele->slug],
            'dimensions' => [
                'longueur' => $this->longueur_mm,
                'largeur' => $this->largeur_mm,
                'hauteur' => $this->hauteur_mm,
            ],
            // Les fichiers privés passent par l'application : jamais d'URL de stockage interne.
            'modele_3d' => $this->chemin_modele_3d ? route('pieces.fichier', [$this->reference, 'glb']) : null,
            'stl' => $this->chemin_stl ? route('pieces.fichier', [$this->reference, 'stl']) : null,
            'miniature' => $this->chemin_miniature ? Storage::disk('s3')->url($this->chemin_miniature) : null,
        ];
    }
}
