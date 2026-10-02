<?php

namespace App\Search;

use Illuminate\Http\Request;

/**
 * État du catalogue tel qu'il apparaît dans l'URL :
 * ?q=jante&echelle=1-18,1-43&fabricant=mecaminia&tri=nom&page=2
 *
 * Les valeurs non reconnues sont ignorées plutôt que rejetées : une URL
 * partagée ou ancienne doit toujours afficher un catalogue.
 */
final readonly class FiltresCatalogue
{
    /**
     * Facettes, dans l'ordre d'affichage, avec leur libellé.
     */
    public const FACETTES = [
        'echelle' => 'Échelle',
        'categorie' => 'Catégorie',
        'modele' => 'Modèle concerné',
        'fabricant' => 'Fabricant',
        'materiau' => 'Matériau',
        'periode' => 'Période',
    ];

    /**
     * Clé d'URL => tri Meilisearch. « pertinence » n'impose aucun tri.
     */
    public const TRIS = [
        'pertinence' => [],
        'recent' => ['cree_le:desc'],
        'nom' => ['nom:asc'],
        'reference' => ['reference:asc'],
        'echelle' => ['echelle_rapport:asc', 'nom:asc'],
        'longueur' => ['longueur_mm:desc'],
    ];

    public const PAR_PAGE = 24;

    /**
     * Meilisearch ne pagine pas au-delà de maxTotalHits (10 000) : 416 pages de 24.
     */
    public const PAGE_MAX = 416;

    private const VALEURS_MAX_PAR_FACETTE = 20;

    /**
     * @param  array<string, list<string>>  $selection  slugs retenus par facette
     */
    public function __construct(
        public string $recherche = '',
        public array $selection = [],
        public ?int $document = null,
        public string $tri = 'pertinence',
        public int $page = 1,
    ) {}

    public static function depuisRequete(Request $request): self
    {
        $selection = [];
        foreach (array_keys(self::FACETTES) as $facette) {
            $valeurs = array_values(array_unique(array_filter(
                explode(',', (string) $request->query($facette, '')),
                fn (string $valeur) => preg_match('/^[a-z0-9-]{1,80}$/', $valeur) === 1,
            )));
            if ($valeurs !== []) {
                $selection[$facette] = array_slice($valeurs, 0, self::VALEURS_MAX_PAR_FACETTE);
            }
        }

        $recherche = mb_substr(trim((string) $request->query('q', '')), 0, 100);
        $tri = (string) $request->query('tri', '');
        $document = filter_var($request->query('document'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $page = filter_var($request->query('page'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => self::PAGE_MAX]]);

        return new self(
            recherche: $recherche,
            selection: $selection,
            document: $document === false ? null : $document,
            tri: array_key_exists($tri, self::TRIS) ? $tri : ($recherche === '' ? 'recent' : 'pertinence'),
            page: $page === false ? 1 : $page,
        );
    }

    /**
     * Filtres Meilisearch (combinés en ET), en excluant éventuellement une
     * facette pour calculer ses compteurs disjonctifs.
     *
     * @return list<string>
     */
    public function expressionsFiltre(?string $sauf = null): array
    {
        $expressions = [];
        foreach ($this->selection as $facette => $valeurs) {
            if ($facette === $sauf) {
                continue;
            }
            // Les valeurs ont passé la regex de depuisRequete : pas de guillemet possible.
            $expressions[] = $facette.' IN ['.implode(', ', array_map(fn (string $v) => '"'.$v.'"', $valeurs)).']';
        }
        if ($this->document !== null) {
            $expressions[] = 'document_ids = '.$this->document;
        }

        return $expressions;
    }

    /**
     * @return list<string>
     */
    public function triMeilisearch(): array
    {
        return self::TRIS[$this->tri];
    }

    /**
     * La sélection est convertie en objet pour être sérialisée en {} même vide.
     *
     * @return array{q: string, selection: object, document: ?int, tri: string, page: int}
     */
    public function versTableau(): array
    {
        return [
            'q' => $this->recherche,
            'selection' => (object) $this->selection,
            'document' => $this->document,
            'tri' => $this->tri,
            'page' => $this->page,
        ];
    }
}
