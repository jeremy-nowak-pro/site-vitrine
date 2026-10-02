<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Table('pieces')]
#[Fillable([
    'reference', 'nom', 'description',
    'categorie_id', 'echelle_id', 'fabricant_id', 'materiau_id', 'periode_id', 'modele_id',
    'longueur_mm', 'largeur_mm', 'hauteur_mm',
    'chemin_modele_3d', 'chemin_stl', 'chemin_miniature',
])]
class Piece extends Model
{
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
