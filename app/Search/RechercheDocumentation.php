<?php

namespace App\Search;

use App\Enums\TypeDocument;
use App\Models\Document;
use Laravel\Scout\EngineManager;
use Laravel\Scout\Engines\MeilisearchEngine;
use Meilisearch\Contracts\SearchQuery;

/**
 * Liste de la documentation servie par Meilisearch : recherche plein texte,
 * filtre par type et compteurs par type. Quand un type est choisi, une
 * seconde requête sans ce filtre donne les compteurs des autres types.
 */
class RechercheDocumentation
{
    public const PAR_PAGE = 20;

    private const CHAMPS_LISTE = ['id', 'titre', 'slug', 'type', 'type_libelle', 'auteur', 'version', 'date_mise_a_jour', 'pieces_count', 'extrait'];

    public function __construct(private readonly EngineManager $moteurs) {}

    /**
     * @return array{
     *     documents: list<array<string, mixed>>,
     *     total: int,
     *     pages: int,
     *     types: list<array{valeur: string, libelle: string, nombre: int}>
     * }
     */
    public function rechercher(string $recherche, ?TypeDocument $type, int $page): array
    {
        /** @var MeilisearchEngine $meilisearch */
        $meilisearch = $this->moteurs->engine('meilisearch');
        $index = (new Document)->searchableAs();

        $requetes = [
            (new SearchQuery)
                ->setIndexUid($index)
                ->setQuery($recherche)
                ->setFilter($type ? ['type = "'.$type->value.'"'] : [])
                ->setFacets(['type'])
                ->setSort($recherche === '' ? ['date_tri:desc'] : [])
                ->setAttributesToRetrieve(self::CHAMPS_LISTE)
                ->setMatchingStrategy('all')
                ->setHitsPerPage(self::PAR_PAGE)
                ->setPage($page),
        ];
        if ($type) {
            $requetes[] = (new SearchQuery)
                ->setIndexUid($index)
                ->setQuery($recherche)
                ->setFacets(['type'])
                ->setMatchingStrategy('all')
                ->setLimit(0);
        }

        $reponses = $meilisearch->multiSearch($requetes)['results'];
        $distribution = $reponses[$type ? 1 : 0]['facetDistribution']['type'] ?? [];

        return [
            'documents' => $reponses[0]['hits'],
            'total' => (int) $reponses[0]['totalHits'],
            'pages' => (int) $reponses[0]['totalPages'],
            'types' => array_map(fn (TypeDocument $t) => [
                'valeur' => $t->value,
                'libelle' => $t->libelle(),
                'nombre' => $distribution[$t->value] ?? 0,
            ], TypeDocument::cases()),
        ];
    }
}
