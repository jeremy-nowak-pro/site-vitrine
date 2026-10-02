<?php

namespace App\Http\Resources;

use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Document
 */
class DocumentLieResource extends JsonResource
{
    public const COLONNES = ['documents.id', 'documents.titre', 'documents.slug', 'documents.type', 'documents.version', 'documents.date_mise_a_jour'];

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'titre' => $this->titre,
            'slug' => $this->slug,
            'type' => $this->type->value,
            'type_libelle' => $this->type->libelle(),
            'version' => $this->version,
            'date_mise_a_jour' => $this->date_mise_a_jour->toDateString(),
        ];
    }
}
