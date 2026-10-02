<?php

namespace App\Models;

use App\Enums\TypeDocument;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Table('documents')]
#[Fillable(['titre', 'slug', 'type', 'contenu', 'version', 'auteur', 'date_mise_a_jour'])]
class Document extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => TypeDocument::class,
            'date_mise_a_jour' => 'date',
        ];
    }

    /**
     * @return BelongsToMany<Piece, $this>
     */
    public function pieces(): BelongsToMany
    {
        return $this->belongsToMany(Piece::class);
    }
}
