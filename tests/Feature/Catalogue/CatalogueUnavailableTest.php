<?php

namespace Tests\Feature\Catalogue;

use App\Search\RechercheCatalogue;
use Inertia\Testing\AssertableInertia as Assert;
use RuntimeException;
use Tests\TestCase;

class CatalogueUnavailableTest extends TestCase
{
    public function test_catalogue_still_renders_when_meilisearch_is_down(): void
    {
        $this->mock(RechercheCatalogue::class)
            ->shouldReceive('rechercher')
            ->andThrow(new RuntimeException('connexion refusée'));

        $this->get('/?echelle=1-18')
            ->assertOk()
            ->assertDontSee('connexion refusée')
            ->assertInertia(fn (Assert $page) => $page
                ->component('Catalogue/Index')
                ->where('resultats', null)
                ->where('filtres.selection.echelle', ['1-18']));
    }
}
