<?php

namespace App\Models;

use App\Enums\TypeDocument;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;
use Laravel\Scout\Searchable;

#[Table('documents')]
#[Fillable(['titre', 'slug', 'type', 'contenu', 'version', 'auteur', 'date_mise_a_jour'])]
class Document extends Model
{
    use Searchable;

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

    public function searchableAs(): string
    {
        return 'documents';
    }

    /**
     * Le contenu est indexé en texte brut pour la recherche ; l'extrait sert à la liste.
     *
     * @return array<string, mixed>
     */
    public function toSearchableArray(): array
    {
        $texte = Str::squish(strip_tags(str_replace('<', ' <', $this->contenu)));

        return [
            'id' => $this->id,
            'titre' => $this->titre,
            'slug' => $this->slug,
            'type' => $this->type->value,
            'type_libelle' => $this->type->libelle(),
            'auteur' => $this->auteur,
            'version' => $this->version,
            'date_mise_a_jour' => $this->date_mise_a_jour->toDateString(),
            'date_tri' => $this->date_mise_a_jour->getTimestamp(),
            'pieces_count' => $this->pieces_count ?? $this->pieces()->count(),
            'extrait' => Str::limit($texte, 220),
            'texte' => $texte,
        ];
    }

    /**
     * @param  Builder<Document>  $query
     * @return Builder<Document>
     */
    protected function makeAllSearchableUsing(Builder $query): Builder
    {
        return $query->withCount('pieces');
    }

    /**
     * @return BelongsToMany<Piece, $this>
     */
    public function pieces(): BelongsToMany
    {
        return $this->belongsToMany(Piece::class);
    }
}
