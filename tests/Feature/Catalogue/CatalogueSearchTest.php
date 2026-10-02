<?php

namespace Tests\Feature\Catalogue;

use App\Models\Document;
use App\Models\Piece;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\Group;
use Tests\Concerns\IndexeMeilisearch;
use Tests\TestCase;

/**
 * Intégration avec le Meilisearch de Sail, sur des index préfixés « test_ »
 * pour ne pas toucher aux données de démonstration.
 */
#[Group('meilisearch')]
class CatalogueSearchTest extends TestCase
{
    use IndexeMeilisearch, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->indexerPourLesTests(pieces: 300, documents: 10);
    }

    protected function tearDown(): void
    {
        $this->supprimerIndexDeTest();
        parent::tearDown();
    }

    public function test_facet_counts_are_disjunctive_and_match_the_database(): void
    {
        $echelles = DB::table('pieces')
            ->join('echelles', 'echelles.id', '=', 'pieces.echelle_id')
            ->join('categories', 'categories.id', '=', 'pieces.categorie_id')
            ->where('categories.slug', 'volants')
            ->groupBy('echelles.slug')
            ->selectRaw('echelles.slug, count(*) as total')
            ->pluck('total', 'slug');

        $this->get('/?categorie=volants&echelle=1-18')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Catalogue/Index')
                ->where('resultats.total', $echelles['1-18'])
                ->where('resultats.facettes.0.cle', 'echelle')
                ->where('resultats.facettes.0.options', fn ($options) => collect($options)
                    ->every(fn ($option) => $option['nombre'] === $echelles[$option['valeur']]
                        && $option['actif'] === ($option['valeur'] === '1-18'))));
    }

    public function test_exact_reference_returns_that_piece_first(): void
    {
        $piece = Piece::findOrFail(123);

        $this->get('/?q='.$piece->reference)
            ->assertInertia(fn (Assert $page) => $page
                ->where('resultats.total', 1)
                ->where('resultats.pieces.0.reference', $piece->reference));
    }

    public function test_document_filter_lists_only_linked_pieces(): void
    {
        $document = Document::withCount('pieces')->firstOrFail();

        $this->get('/?document='.$document->id)
            ->assertInertia(fn (Assert $page) => $page
                ->where('document.titre', $document->titre)
                ->where('resultats.total', $document->pieces_count));
    }

    public function test_results_are_paginated_by_24(): void
    {
        $this->get('/?page=2&tri=reference')
            ->assertInertia(fn (Assert $page) => $page
                ->where('resultats.total', 300)
                ->where('resultats.pages', 13)
                ->has('resultats.pieces', 24)
                ->where('resultats.pieces.0.reference', Piece::orderBy('reference')->skip(24)->value('reference')));
    }
}
