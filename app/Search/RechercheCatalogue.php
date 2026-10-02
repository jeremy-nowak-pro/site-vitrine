<?php

namespace App\Search;

use App\Models\Categorie;
use App\Models\Echelle;
use App\Models\Fabricant;
use App\Models\Materiau;
use App\Models\Modele;
use App\Models\Periode;
use App\Models\Piece;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Laravel\Scout\EngineManager;
use Laravel\Scout\Engines\MeilisearchEngine;
use Meilisearch\Contracts\SearchQuery;

/**
 * Recherche du catalogue en un seul aller-retour Meilisearch (multi-search) :
 * - une requête principale : résultats paginés et compteurs des facettes
 *   sans sélection ;
 * - une requête par facette active, sans son propre filtre, pour afficher le
 *   nombre de résultats qu'apporterait chaque autre valeur (facettes
 *   disjonctives : OU dans une facette, ET entre facettes).
 */
class RechercheCatalogue
{
    private const CHAMPS_CARTE = [
        'id', 'reference', 'nom', 'categorie_nom', 'echelle_libelle', 'fabricant_nom', 'modele_nom', 'miniature',
    ];

    public function __construct(private readonly EngineManager $moteurs) {}

    /**
     * @return array{
     *     pieces: list<array<string, mixed>>,
     *     total: int,
     *     pages: int,
     *     tronque: bool,
     *     facettes: list<array{cle: string, libelle: string, options: list<array{valeur: string, libelle: string, nombre: int, actif: bool}>}>
     * }
     */
    public function rechercher(FiltresCatalogue $filtres): array
    {
        /** @var MeilisearchEngine $meilisearch */
        $meilisearch = $this->moteurs->engine('meilisearch');
        $index = (new Piece)->searchableAs();
        $facettes = array_keys(FiltresCatalogue::FACETTES);

        $requetes = [
            (new SearchQuery)
                ->setIndexUid($index)
                ->setQuery($filtres->recherche)
                ->setFilter($filtres->expressionsFiltre())
                ->setFacets($facettes)
                ->setSort($filtres->triMeilisearch())
                ->setAttributesToRetrieve(self::CHAMPS_CARTE)
                // Tous les mots doivent correspondre : une référence comme « ALU-JA18 »
                // ne remonte plus les pièces qui ne partagent qu'un mot.
                ->setMatchingStrategy('all')
                ->setHitsPerPage(FiltresCatalogue::PAR_PAGE)
                ->setPage($filtres->page),
        ];
        $facettesActives = array_keys($filtres->selection);
        foreach ($facettesActives as $facette) {
            $requetes[] = (new SearchQuery)
                ->setIndexUid($index)
                ->setQuery($filtres->recherche)
                ->setFilter($filtres->expressionsFiltre(sauf: $facette))
                ->setFacets([$facette])
                ->setMatchingStrategy('all')
                ->setLimit(0);
        }

        $reponses = $meilisearch->multiSearch($requetes)['results'];
        $principale = $reponses[0];

        $distributions = $principale['facetDistribution'] ?? [];
        foreach ($facettesActives as $rang => $facette) {
            $distributions[$facette] = $reponses[$rang + 1]['facetDistribution'][$facette] ?? [];
        }

        // Chaque pièce a exactement une catégorie : la somme de cette facette donne le
        // total exact, là où totalHits plafonne à maxTotalHits.
        $total = array_sum($principale['facetDistribution']['categorie'] ?? []);
        $disque = Storage::disk('s3');

        return [
            'pieces' => array_map(fn (array $hit) => [
                ...array_diff_key($hit, ['miniature' => true]),
                'miniature' => $hit['miniature'] ? $disque->url($hit['miniature']) : null,
            ], $principale['hits']),
            'total' => $total,
            'pages' => (int) $principale['totalPages'],
            'tronque' => $total > $principale['totalHits'],
            'facettes' => $this->facettes($distributions, $filtres->selection),
        ];
    }

    /**
     * @param  array<string, array<string, int>>  $distributions
     * @param  array<string, list<string>>  $selection
     * @return list<array{cle: string, libelle: string, options: list<array{valeur: string, libelle: string, nombre: int, actif: bool}>}>
     */
    private function facettes(array $distributions, array $selection): array
    {
        $referentiels = $this->referentiels();
        $resultat = [];

        foreach (FiltresCatalogue::FACETTES as $cle => $libelle) {
            $options = [];
            foreach ($referentiels[$cle] as $valeur => $libelleValeur) {
                $nombre = $distributions[$cle][$valeur] ?? 0;
                $actif = in_array($valeur, $selection[$cle] ?? [], true);
                if ($nombre > 0 || $actif) {
                    $options[] = ['valeur' => $valeur, 'libelle' => $libelleValeur, 'nombre' => $nombre, 'actif' => $actif];
                }
            }
            $resultat[] = ['cle' => $cle, 'libelle' => $libelle, 'options' => $options];
        }

        return $resultat;
    }

    /**
     * Libellés des valeurs de facettes, dans leur ordre naturel. Ces tables
     * changent rarement : une heure de cache suffit.
     *
     * @return array<string, array<string, string>>
     */
    public function referentiels(): array
    {
        return Cache::remember('catalogue:referentiels', 3600, fn () => [
            'echelle' => Echelle::query()->orderBy('rapport')->pluck('libelle', 'slug')->all(),
            'categorie' => Categorie::query()->orderBy('id')->pluck('nom', 'slug')->all(),
            'modele' => Modele::query()->orderBy('nom')->pluck('nom', 'slug')->all(),
            'fabricant' => Fabricant::query()->orderBy('nom')->pluck('nom', 'slug')->all(),
            'materiau' => Materiau::query()->orderBy('nom')->pluck('nom', 'slug')->all(),
            'periode' => Periode::query()->orderBy('id')->pluck('libelle', 'slug')->all(),
        ]);
    }
}
