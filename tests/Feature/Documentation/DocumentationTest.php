<?php

namespace Tests\Feature\Documentation;

use App\Enums\TypeConsultation;
use App\Enums\TypeDocument;
use App\Models\Consultation;
use App\Models\Document;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\Group;
use Tests\Concerns\IndexeMeilisearch;
use Tests\TestCase;

#[Group('meilisearch')]
class DocumentationTest extends TestCase
{
    use IndexeMeilisearch, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->indexerPourLesTests(pieces: 200, documents: 20);
    }

    protected function tearDown(): void
    {
        $this->supprimerIndexDeTest();
        parent::tearDown();
    }

    public function test_type_filter_keeps_the_count_of_every_type(): void
    {
        $parType = Document::query()->toBase()->groupBy('type')->selectRaw('type, count(*) as total')->pluck('total', 'type');

        $this->get('/documentation?type='.TypeDocument::FicheTechnique->value)
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Documentation/Index')
                ->where('resultats.total', $parType[TypeDocument::FicheTechnique->value])
                ->where('resultats.documents', fn ($documents) => collect($documents)->every(fn ($d) => $d['type'] === 'fiche_technique'))
                ->where('resultats.types', fn ($types) => collect($types)->every(fn ($t) => $t['nombre'] === ($parType[$t['valeur']] ?? 0))));
    }

    public function test_full_text_search_finds_a_procedure_by_its_content(): void
    {
        $this->get('/documentation?q='.urlencode('cyanoacrylate fluide'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('resultats.documents.0.titre', 'Pliage des photodécoupes en laiton'));
    }

    public function test_document_page_serves_sanitized_html_and_linked_pieces(): void
    {
        $this->withoutDefer();
        $document = Document::withCount('pieces')->firstOrFail();
        $document->update(['contenu' => $document->contenu.'<script>alert(1)</script><p onclick="x()">Fin</p>']);

        $this->get('/documentation/'.$document->slug)
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Documentation/Show')
                ->where('document.html', fn ($html) => ! str_contains($html, 'script') && ! str_contains($html, 'onclick') && str_ends_with($html, '<p>Fin</p>'))
                ->where('nombrePieces', $document->pieces_count)
                ->has('pieces', min(40, $document->pieces_count))
                ->missing('document.contenu'));

        $this->assertDatabaseHas(Consultation::class, ['type' => TypeConsultation::Document->value, 'consultable_id' => $document->id]);
    }

    public function test_unknown_document_returns_404(): void
    {
        $this->get('/documentation/inexistant')->assertNotFound();
    }
}
