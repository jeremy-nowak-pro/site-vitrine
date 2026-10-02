<?php

namespace Tests\Unit\Search;

use App\Search\FiltresCatalogue;
use Illuminate\Http\Request;
use PHPUnit\Framework\TestCase;

class FiltresCatalogueTest extends TestCase
{
    public function test_it_reads_comma_separated_facet_values_from_the_url(): void
    {
        $filtres = FiltresCatalogue::depuisRequete(Request::create('/', 'GET', [
            'q' => '  volant ',
            'echelle' => '1-18,1-43,1-18',
            'categorie' => 'volants',
            'tri' => 'nom',
            'page' => '3',
        ]));

        $this->assertSame('volant', $filtres->recherche);
        $this->assertSame(['echelle' => ['1-18', '1-43'], 'categorie' => ['volants']], $filtres->selection);
        $this->assertSame('nom', $filtres->tri);
        $this->assertSame(3, $filtres->page);
    }

    public function test_it_drops_values_that_could_alter_the_meilisearch_filter(): void
    {
        $filtres = FiltresCatalogue::depuisRequete(Request::create('/', 'GET', [
            'echelle' => '1-18,"] OR id > 0,Majuscules',
            'tri' => 'inconnu',
            'page' => '9999',
            'document' => 'abc',
        ]));

        $this->assertSame(['echelle' => ['1-18']], $filtres->selection);
        $this->assertSame('recent', $filtres->tri);
        $this->assertSame(1, $filtres->page);
        $this->assertNull($filtres->document);
    }

    public function test_default_sort_depends_on_the_presence_of_a_query(): void
    {
        $this->assertSame('recent', FiltresCatalogue::depuisRequete(Request::create('/'))->tri);
        $this->assertSame('pertinence', FiltresCatalogue::depuisRequete(Request::create('/', 'GET', ['q' => 'jante']))->tri);
    }

    public function test_filter_expressions_can_exclude_one_facet_for_disjunctive_counts(): void
    {
        $filtres = new FiltresCatalogue(selection: ['echelle' => ['1-18', '1-43'], 'categorie' => ['volants']], document: 7);

        $this->assertSame(
            ['echelle IN ["1-18", "1-43"]', 'categorie IN ["volants"]', 'document_ids = 7'],
            $filtres->expressionsFiltre(),
        );
        $this->assertSame(['categorie IN ["volants"]', 'document_ids = 7'], $filtres->expressionsFiltre(sauf: 'echelle'));
    }
}
