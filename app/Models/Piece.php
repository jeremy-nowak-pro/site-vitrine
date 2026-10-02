<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection as BaseCollection;
use Laravel\Scout\Searchable;

#[Table('pieces')]
#[Fillable([
    'reference', 'nom', 'description',
    'categorie_id', 'echelle_id', 'fabricant_id', 'materiau_id', 'periode_id', 'modele_id',
    'longueur_mm', 'largeur_mm', 'hauteur_mm',
    'chemin_modele_3d', 'chemin_stl', 'chemin_miniature',
])]
class Piece extends Model
{
    use Searchable;

    /**
     * Relations nécessaires à l'indexation, chargées en une requête par relation.
     */
    public const RELATIONS_INDEX = [
        'categorie:id,slug,nom',
        'echelle:id,slug,libelle,rapport',
        'fabricant:id,slug,nom',
        'materiau:id,slug,nom',
        'periode:id,slug,libelle',
        'modele:id,slug,nom',
        'documents:id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'longueur_mm' => 'float',
            'largeur_mm' => 'float',
            'hauteur_mm' => 'float',
        ];
    }

    public function searchableAs(): string
    {
        return 'pieces';
    }

    /**
     * Document dénormalisé : les facettes filtrent sur les slugs (valeurs de
     * l'URL), les libellés servent à l'affichage des cartes sans requête SQL.
     *
     * @return array<string, mixed>
     */
    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'reference' => $this->reference,
            'nom' => $this->nom,
            'description' => $this->description,
            'categorie' => $this->categorie->slug,
            'categorie_nom' => $this->categorie->nom,
            'echelle' => $this->echelle->slug,
            'echelle_libelle' => $this->echelle->libelle,
            'echelle_rapport' => $this->echelle->rapport,
            'fabricant' => $this->fabricant->slug,
            'fabricant_nom' => $this->fabricant->nom,
            'materiau' => $this->materiau->slug,
            'materiau_nom' => $this->materiau->nom,
            'periode' => $this->periode->slug,
            'periode_libelle' => $this->periode->libelle,
            'modele' => $this->modele->slug,
            'modele_nom' => $this->modele->nom,
            'document_ids' => $this->documents->pluck('id')->all(),
            'longueur_mm' => $this->longueur_mm,
            'largeur_mm' => $this->largeur_mm,
            'hauteur_mm' => $this->hauteur_mm,
            'miniature' => $this->chemin_miniature,
            'cree_le' => $this->created_at?->getTimestamp(),
        ];
    }

    /**
     * @param  Builder<Piece>  $query
     * @return Builder<Piece>
     */
    protected function makeAllSearchableUsing(Builder $query): Builder
    {
        return $query->with(self::RELATIONS_INDEX);
    }

    /**
     * Indexation d'une pièce isolée (sauvegarde) : complète les relations manquantes.
     *
     * @param  BaseCollection<int, Piece>  $models
     * @return BaseCollection<int, Piece>
     */
    public function makeSearchableUsing(BaseCollection $models): BaseCollection
    {
        return $models instanceof Collection ? $models->loadMissing(self::RELATIONS_INDEX) : $models;
    }

    /**
     * @return BelongsTo<Categorie, $this>
     */
    public function categorie(): BelongsTo
    {
        return $this->belongsTo(Categorie::class);
    }

    /**
     * @return BelongsTo<Echelle, $this>
     */
    public function echelle(): BelongsTo
    {
        return $this->belongsTo(Echelle::class);
    }

    /**
     * @return BelongsTo<Fabricant, $this>
     */
    public function fabricant(): BelongsTo
    {
        return $this->belongsTo(Fabricant::class);
    }

    /**
     * @return BelongsTo<Materiau, $this>
     */
    public function materiau(): BelongsTo
    {
        return $this->belongsTo(Materiau::class);
    }

    /**
     * @return BelongsTo<Periode, $this>
     */
    public function periode(): BelongsTo
    {
        return $this->belongsTo(Periode::class);
    }

    /**
     * @return BelongsTo<Modele, $this>
     */
    public function modele(): BelongsTo
    {
        return $this->belongsTo(Modele::class);
    }

    /**
     * Pièces compatibles ou liées. La relation est stockée dans les deux sens.
     *
     * @return BelongsToMany<Piece, $this>
     */
    public function compatibles(): BelongsToMany
    {
        return $this->belongsToMany(Piece::class, 'piece_compatibilite', 'piece_id', 'compatible_id');
    }

    /**
     * @return BelongsToMany<Document, $this>
     */
    public function documents(): BelongsToMany
    {
        return $this->belongsToMany(Document::class);
    }
}
